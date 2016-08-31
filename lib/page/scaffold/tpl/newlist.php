<?php $scaffold_helper->beforeListRender(); ?>
<?php
$canCreate = ! isset($scaffold_config['can_create']) || $scaffold_config['can_create'];
?>
<h2><a class="h2-tit h2-current" href="#"><?php echo $title?></a></h2>
<?php if ($canCreate) :?>
<div class="add-new">
	<a class="sdc-form-button sdc-add" href="<?php echo v_($scaffold_config, 'create_url', "/{$scaffold_config['controller_directory']}{$scaffold_config['controller']}/create?redirect_uri=" . urlencode(get_self_full_url()))?>">创建<?php echo $instanceName?></a>
</div>
<?php endif;?>
<?php
    $columns = $scaffold_config['list']['columns'];
?>

<?php $scaffold_helper->searchFormRender();?>

<?php $scaffold_helper->beforeListTableRender(); ?>
<div class="grid-wrapper mt10">
    <div class="page-top">
       <div class="sdc-page">
            <?php echo $scaffold_pagination?>
        </div>
    </div>
    <table class="sdc-grid">
        <?php $scaffold_helper->beforeListTableHeadRender();?>
        <tr>
        <?php foreach ($columns as $column_name => $ignor) :?>     
        <?php
            //{列属性}列名称
            preg_match('@^\{(.+?)\}(.+)$@', $column_name, $ma);
            $column_attrs = '';
            if ($ma) {
                $column_attrs = $ma[1];
                $column_name = $ma[2];
            }
            if ($column_name == '__checkbox__') {
                $column_attrs = 'width="20"';
            }
        ?>
        <th>
            <?php if ($column_name == '__checkbox__') : ?>
            <input type="checkbox" class="sel-all" id="select_all"/>
            <?php elseif (! $scaffold_helper->headColumnRender($column_name)) :?>
            <?php echo $column_name?>
            <?php endif;?>
        </th>
        <?php endforeach;?>
        <th>操作 <a href="javascript:;" class="icon-16 icon-16-help" title="帮助" data-icon="icon-16-setting "></a></th>
        </tr>
        <?php $scaffold_helper->afterListTableHeadRender();?>
        
        <?php if (empty($scaffold_items)) :?>
        <tr>
            <td colspan="8">
            <p class="empty-notice">
                    暂无数据
            </p>
            </td>
        </tr>
        <?php else :?>
        
        <?php foreach ($scaffold_items as $i => $scaffold_item) :?>
        <tr>
            <?php foreach ($columns as $column_index => $method) :?>
            <td class="tc">
                <?php if ($column_index == '__checkbox__') :?>
                <input type="checkbox" class="sel-item" value="<?php echo $scaffold_item[$scaffold_config['primary_key']]?>"/>
                <?php elseif (isset($scaffold_item[$method])) :?>
                <?php echo $scaffold_item[$method]?>
                <?php elseif ($method == '__LINE__') :?>
                <?php echo $column_index + 1 ?>
                <?php elseif (strpos($method, 'cb_') !== FALSE) :?>
                <?php echo $scaffold_helper->$method($scaffold_item)?>
                <?php else :?>
                <?php echo $scaffold_helper->processTpl($method, $scaffold_item)?>
                <?php endif;?>
            </td>
            <?php endforeach;?>
            <td class="tc">
                <?php $scaffold_helper->beforeOpColumnRender($scaffold_item); ?>
                <?php if ( ! $scaffold_helper->OpColumnRender($scaffold_item)) :?>
                <?php echo $scaffold_helper->editLink($scaffold_config, $scaffold_item)?> 
                
                <?php echo $scaffold_helper->deleteLink($scaffold_config, $scaffold_item)?>
                <?php endif;?>
                <?php $scaffold_helper->afterOpColumnRender($scaffold_item); ?>
            </td>
        </tr>
        <?php endforeach;endif;?>
        <!-- <tfoot>
        <?php $scaffold_helper->beforeListTableFootRender();?>
        <?php if (isset($columns['__checkbox__'])) :?>
        <tr class="dark">
            <td colspan="<?php echo count($columns) + 1?>">
                <input type="checkbox" class="sel-all"/>
                <?php if ( ! $scaffold_helper->batchActionRender()) :?>
                <input type="button" class="batch-del-btn" value="删除"/>
                <?php endif;?>
            </td>
        </tr>
        <?php endif;?>
        <tr>
            <td colspan="<?php echo count($columns) + 1?>" class="pages">
                <?php echo $scaffold_pagination?>
            </td>
        </tr>
        <?php $scaffold_helper->afterListTableFootRender();?>
    </tfoot> -->
    </table>
    <div class="page-bottom">
        <div class="sdc-page">
            <?php echo $scaffold_pagination?>
        </div>
    </div>
</div>

<?php $scaffold_helper->afterListTableRender(); ?>
<form id="delete-form" action="/<?php echo $scaffold_config['controller_directory'].$scaffold_config['controller']?>/delete" method="post" style="display:none">
    <input type="hidden" name="<?php echo $scaffold_config['primary_key']?>" value=""/>
</form>

<script>
$(function(){
    $('.icon-16-del').click(function(){
        var id = $(this).attr('rel');
        if ( ! confirm('你确定要删除记录吗？')) {
            return;
        }
        $('#delete-form').find(':hidden').val(id);
        
        <?php if (@$scaffold_config['ajax']) :?>
        popup_msg('删除中...', 'info');
        $.post($('#delete-form').attr('action'), $('#delete-form').serialize(), function(ret){
            if (ret.code === 0) {
                popup_msg(ret.msg, 'succ');
                form_succ();
            } else {
                popup_msg(ret.msg, 'error');
            }
        }, 'json');
        <?php else :?>
        $('#delete-form').get(0).submit();
        <?php endif;?>
    });
    
    function get_selected_ids()
    {
        return $(':checkbox.sel-item:checked').map(function(){
            return this.value;
        }).get().join(',');
    }
    
    window.get_selected_ids = get_selected_ids;
    
    $('.batch-del-btn').click(function(){
        var checkedUids = get_selected_ids();
        if ( ! checkedUids) {
            popup_msg('未选择记录', 'error');
            return;
        }
        if ( ! confirm('你确定要删除所选记录吗？')) {
            return;
        }
        $('#delete-form').find(':hidden').val(checkedUids).end()
        .get(0).submit();
    });
    
    $(':checkbox.sel-all,:checkbox.sel-item').click(function(){
        var $t = $(this), isChecked = $t.attr('checked');
        if ($t.is('.sel-all')) {
            if (isChecked) {
                $(':checkbox.sel-item,:checkbox.sel-all').attr('checked', true);
            } else {
                $(':checkbox.sel-item,:checkbox.sel-all').attr('checked', false);
            }
        } else {
            if (isChecked) {
                if ($(':checkbox.sel-item:not(:checked)').length == 0) {
                    $(':checkbox.sel-all').attr('checked', true);    
                }
            } else {
                $(':checkbox.sel-all').attr('checked', false);
            }
        }
    });
    
    $('.list-table tbody').live('click', function(event){
        var $target = $(event.target);
        if ($target.is('a,input,select')) {
            return;
        }
        var $checkbox = $target.closest('tr').find(':checkbox');
        if ($checkbox.length) {
            $checkbox.attr('checked', ! $checkbox.attr('checked'));
        }
    });

<?php if (@$scaffold_config['ajax']) :?>
    var $mask = $('<div id="mask" style="position:absolute;left:0;top:0;z-index:1000;width:100%;height:100%;background:#fff;opacity:0.8;filter:alpha(opacity=80);display:none"></div>').appendTo(document.body);
    
    var $edit_box = $('<div id="edit-box" style="width:410px;position:absolute;display:none;z-index:1001;background:#2d2d2d;padding:25px 5px 5px 5px;"><strong class="title" style="position:absolute;left:0px;top:0px;padding-left:5px;line-height:25px;font-size:14px;font-weight:bold;color:#fff" class=""></strong><a href="javascript:;" class="close" style="color:#fff;margin-right:5px;font-weight:normal;position:absolute;right:0;top:0;padding-right:1em;line-height:25px;">关闭</a><div class="content-wrapper" style="background:#fff"><div class="content" style="padding:10px;background:#fff;float:left"></div><div style="clear:both;height:0px;overflow:hidden"></div></div></div>').appendTo(document.body);
    
    $('#edit-box .close').live('click', function(event){
        event.preventDefault();
        $mask.hide();
        $edit_box.hide();
        hide_popup_msg();
    });
    
    window.form_succ = function() {
        $mask.hide();
        $edit_box.hide();
        popup_msg('列表加载中...', 'info');
        $('.list-table').parent().load('<?php echo get_self_full_url()?>', function(){
            hide_popup_msg();
        });
    };

    $('a[href*=/create],a[href*=/edit]').click(function(event){
        event.preventDefault();
        
        var href = $(this).attr('href');

        $mask.css({
            width: $(document).width(),
            height: $(document).height()
        }).show();
        
        if (/create/.test(href)) {
            $edit_box.find('.title').html('添加');
        } else {
            $edit_box.find('.title').html('编辑');
        }

        $edit_box.find('.content').html('<p style="line-height:200px;width:400px;text-align:center;margin:0">Loading...</p>').end().show();
        var w = $edit_box.attr('offsetWidth'), h = $edit_box.attr('offsetHeight'),
        ww = $(window).width(), wh = $(window).height();
        $edit_box.css({
            width : w,
            height : h,
            left : Math.max(0, (ww - w) / 2),
            top : Math.max(0, (wh - h) / 2)
        }).find('.content').load(href, function(){
//            var nh = $edit_box.attr('offsetHeight');
            var nw = $edit_box.find('.content').attr('offsetWidth');
            var nh = $edit_box.find('.content').attr('offsetHeight');
            $edit_box.css({
                left : Math.max(0, (ww - nw) / 2),
                top : Math.max(0, (wh - nh) / 2)
            }).animate({
                width : nw,
                height : nh
            });
        });
    });

<?php endif;?>
});
</script>

<?php $scaffold_helper->afterListRender(); ?>
