/**
 * Created by aiqing on 16/7/26.
 */
var page = require('webpage').create(),
    jqueryUrl = 'jquery.js';
page.open('http://www.baidu.com', function(status){
    console.log('open url:http://www.baidu.com, status:', status);

    var cookies = page.cookies;
    console.log('Listing cookies:');
    for(var i in cookies) {
        //console.log(cookies[i].name + '=' + cookies[i].value);
    }

   login(page,crawl);

    //phantom.exit();
});

page.onResourceRequested = function(requestData, networkRequest) {
    //console.log('Request (#' + requestData.id + '): ' + JSON.stringify(requestData));
}

page.onResourceReceived = function(response) {
  //console.log('Response (#' + response.id + ', stage "' + response.stage + '"): ' + JSON.stringify(response));
};

function login(curPage, crawlFunc){
    curPage.open('http://www.baidu.com', function(status) {
        console.debug('login complete status:', status);
        if (!curPage.injectJs(jqueryUrl)) phantom.exit();
        //login
        curPage.evaluate(function() {
            console.log('include jqeury succ');
            $('#email').val('utopialiu@qq.com');
            $('#password').val('Zhao8118');
        });
        curPage.evaluate(function() {
            $('button').click();
        });
        console.debug('click the login button');

        waitFor(function() {
            return curPage.evaluate(function() {
                console.log()
                return window.location.pathname == '/dashboard/home/';
            });
        }, crawlFunc, 10000);

    });
}

function waitFor(testFx, onReady, timeOutMillis) {
    var maxtimeOutMillis = timeOutMillis ? timeOutMillis : 3000, //< Default Max Timout is 3s
        start = new Date().getTime(),
        condition = false,
        interval = setInterval(function() {
            if ( (new Date().getTime() - start < maxtimeOutMillis) && !condition ) {
                // If not time-out yet and condition not yet fulfilled
                condition = (typeof(testFx) === "string" ? eval(testFx) : testFx()); //< defensive code
            } else {
                if(!condition) {
                    // If condition still not fulfilled (timeout but condition is 'false')
                    console.log("'waitFor()' timeout");
                    phantom.exit(1);
                } else {
                    // Condition fulfilled (timeout and/or condition is 'true')
                    console.log("'waitFor()' finished in " + (new Date().getTime() - start) + "ms.");
                    typeof(onReady) === "string" ? eval(onReady) : onReady(); //< Do what it's supposed to do once the condition is fulfilled
                    clearInterval(interval); //< Stop this interval
                }
            }
        }, 250); //< repeat check every 250ms
}

function crawl() {
   console.log("crawl");
}
