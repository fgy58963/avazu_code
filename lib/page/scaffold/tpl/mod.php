<?php
    $scaffold_helper->beforeModRender();
?>
<h2><?php echo $title?></h2>
<form id="scaffold-form" action="<?php echo get_self_full_url()?>" method="post">
<?php $scaffold_helper->beforeModTableRender(); ?>
<table class="ft">
    <?php
        $postAndGet = $_POST + $_GET;
    ?>
    <?php foreach ($scaffold_config['fields'] as $field_config) :?>
    <?php
        $type = v_($field_config, 'type', 'text');
        if ( ! isset($field_config['rules'])) {
            $field_config['rules'] = '';
        }
    ?>
    <tr <?php if ($type == 'hidden') :?>style="display:none"<?php endif;?>>
        <th style="min-width:75px"><?php echo $field_config['label']?>：</th>
        <td>
            <?php
                $name = $field_config['field'];
                $default = NULL;
                if (isset($postAndGet[$name])) {
                    $default = $postAndGet[$name];
                } else if (isset($scaffold_item[$name])) {
                    $default = $scaffold_item[$name];
                } else if (isset($field_config['default'])) {
                    $default = $field_config['default'];
                }
                if (@$field_config['readonly']) {
                    echo $default;
                    continue;
                }

                //特殊字段支持自定义
                $replaceFilder = $scaffold_helper->replaceModTableFieldRander($field_config, @$scaffold_item);
                if ($replaceFilder) {
                    echo $replaceFilder;
                    continue;
                }
                if ($type == 'text') {
                    $class = "txt";
                    if (strpos($field_config['rules'], 'numeric') !== FALSE) {
                        $class .= ' numeric';
                    } else if (strpos($field_config['rules'], 'alpha') !== FALSE) {
                        $class .= ' alpha';
                    }
                    echo form_input($name, set_value($name, $default), " class='$class'");
                } else if ($type == 'hidden') {
                    echo form_hidden($name, set_value($name, $default));
                } else if ($type == 'textarea') {
                    $class = "txt";
                    if (@$field_config['rich_html']) {
                        $class = "txt rich-edit";
                    }
                    echo form_textarea($name, set_value($name, $default), " class='$class'");
                } else if ($type == 'date') {
                    echo form_input($name, set_value($name, $default), " class='txt date'");
                } else if ($type == 'datetime') {
                    echo form_input($name, set_value($name, $default), " class='txt datetime'");
                } else if ($type == 'image') {
                    $id = rand_str(10);
                    $path = set_value($name, @$scaffold_item[$name]);
                    $url = '';
                    if ($path) {
                        $url = Config::get('Image.Prefix').$path;
                    }
                    $uploadOptions = array(
                        'file' => array('url' => $url, 'path' => $path),
                        'post_params' => array('session' => $_COOKIE[Config::get('Cookie.Session')]),
                        'name' => $name
                    );
                    if (isset($field_config['options'])) {
                        $uploadOptions = array_merge($uploadOptions, $field_config['options']);
                    }
                    $uploadOptions = json_encode($uploadOptions);
                    echo '<div id="img_upload_'.$id.'"></div>';
                    echo '<script>
                    $("#img_upload_'.$id.'").img_uploader('.$uploadOptions.');
                    </script>';
                } else if ($type == 'checkbox') {
                    $options = $field_config['options'];
                    $defaultOptions = array();
                    $item_val = @$scaffold_item[$name];
                    if ($item_val) {
                        $defaultOptions = explode(',', $item_val);
                    }
                    //加入中括号支持数组提交
                    $name .= '[]';
                    foreach ($options as $op_val => $op_name) {
                        $id = "ck_{$name}_{$op_val}";
                        $defaultChecked = in_array($op_val, $defaultOptions) ? TRUE : FALSE;
                        echo form_checkbox($name, $op_val, set_checkbox($name, $op_val, $defaultChecked), "id=\"$id\"");
                        echo "&nbsp;";
                        echo form_label($op_name, $id);
                        echo "&nbsp;&nbsp;";
                    }
                } else if (is_array($type)) {
                    $options = $type;
                    echo form_dropdown($name, $options, $default);
                }
            ?>
            <?php if (@$field_config['exp']) :?>
            <p class="exp"><?php echo $field_config['exp']?></p>
            <?php endif;?>
        </td>
        <td>
            <?php echo form_error($name, '<p class="error">', '</p>')?>
        </td>
    </tr>
    <?php endforeach; ?>
    <tr>
        <td>&nbsp;</td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td>
            <?php echo $form_hash?>
            <?php if (! empty($scaffold_item)) :?>
            <?php echo form_hidden($scaffold_config['primary_key'], $scaffold_item[$scaffold_config['primary_key']])?>
            <?php endif;?>
            <input type="hidden" name="redirect_uri" value="<?php echo set_value('redirect_uri', @$redirect_uri)?>"/>
            <span class="btn_fat btn"><i><input type="submit" value="保存"/></i></span>
            <span class="btn_fat btn" style="margin-left: 30px"><i><button href="#" onclick="history.go(-1);return false;"  style="mr20">返回</button></i></span>
        </td>
        <td>&nbsp;</td>
    </tr>
</table>
<?php $scaffold_helper->afterModTableRender(); ?>
</form>

<script>
$(function(){
    if ($('#scaffold-form :text:first').length) {
        $('#scaffold-form :text:first').get(0).focus();
    }
    var rich_edit_id_index = 1;
	$('textarea.rich-edit').each(function(){
		var id = 'ke-eidt-' + (rich_edit_id_index++);
		$(this).attr('id', id).css({
            width: 600,
            height: 200
        });
        KE.show({
            id : id,
            resizeMode : 1,
            allowUpload : false,
            items : [
            'fontname', 'fontsize', '|', 'textcolor', 'bgcolor', 'bold', 'italic', 'underline',
            'removeformat', '|', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist',
            'insertunorderedlist', '|', 'image', 'link']
		});
	});

    <?php if (@$scaffold_config['ajax']) :?>
    $('#scaffold-form').submit(function(event){
        event.preventDefault();
        $.post($(this).attr('action'), $(this).serialize(), function(ret){
            if (ret.code !== 0) {
                popup_msg(ret.msg, 'error');
            } else {
                popup_msg(ret.msg, 'succ');
                window.form_succ && window.form_succ();
            }
        }, 'json');
    });
    <?php endif;?>
});
</script>
