var share = {
    init: function(element){
        $(document).ready(function(){
            $(element).each(function(idx){
                var countApiUrls = {
                    vk: "https://vk.com/share.php?act=count&index=" + idx + "&url=",
                    facebook: "http://graph.facebook.com/?id="
                };
                var pageUrl = $.parseJSON($(element + " div:eq(0)").attr("data-share-data")).url;
                var id = $.parseJSON($(element + " div:eq(0)").attr("data-share-data")).id;
                if(pageUrl){
                    share.getCountLikes(
                        $(element).find(".twitter"),
                        "twit",
                        id,
                        "twitter"
                    );
                    share.getCountLikes(
                        $(element).find(".vk"),
                        "vk",
                        id,
                        "vk"
                    );
                    share.getCountLikes(
                        $(element).find(".facebook"),
                        "fb",
                        id,
                        "facebook"
                    );
                };
            });
        });
        return false;
    },
    getCountLikes: function(box, apiUrl, pageUrl, type){
        if(apiUrl && pageUrl){
            if(type == "twitter"){
                var tw_cnt = $.ajax({
                    url: "/netcat/modules/default/ajax.php?isNaked=1",
                    async: false,
                    type: "POST",
                    data: {action: 'get_tw_cnt', page: pageUrl}
                }).responseText;
                
                if (tw_cnt > 0) {
                    share.setCountLikes(box, tw_cnt);
                } else {
                    share.setCountLikes(box, '0');
                }
            };
            if(type == "vk"){
                var vk_cnt = $.ajax({
                    url: "/netcat/modules/default/ajax.php?isNaked=1",
                    async: false,
                    type: "POST",
                    data: {action: 'get_vk_cnt', page: pageUrl}
                }).responseText;
                
                if (vk_cnt > 0) {
                    share.setCountLikes(box, vk_cnt);
                } else {
                    share.setCountLikes(box, '0');
                }
            };
            if(type == "facebook"){
                var fb_cnt = $.ajax({
                    url: "/netcat/modules/default/ajax.php?isNaked=1",
                    async: false,
                    type: "POST",
                    data: {action: 'get_fb_cnt', page: pageUrl}
                }).responseText;
                
                if (fb_cnt > 0) {
                    share.setCountLikes(box, fb_cnt);
                } else {
                    share.setCountLikes(box, '0');
                }
            };
            
        };
        return false;
    },
    setCountLikes: function(box, num){
        box.append("<span class='count'>" + num + "</span>");
        return false;
    },
    twitter: function($this){
        var data = share.data($this);
        if(data){
            var url  = "http://twitter.com/share?";
                url += "text="      + encodeURIComponent(data.title);
                url += "&url="      + encodeURIComponent(data.url);
                url += "&hashtags=" + "";
                url += "&counturl=" + encodeURIComponent(data.url);
            share.popup($this, url, data.id, 't');
        };
        return false;
    },
    vk: function($this){
        var data = share.data($this);
        if(data){
            var url  = 'http://vkontakte.ru/share.php?';
                url += 'url='          + encodeURIComponent(data.url);
                url += '&title='       + encodeURIComponent(data.title);
                url += '&description=' + encodeURIComponent(data.text);
                url += '&image='       + encodeURIComponent(data.img);
                url += '&noparse=true';
            share.popup($this, url, data.id, 'v');
        };
        return false;
    },
    facebook: function($this){
        var data = share.data($this);
        if(data){
            var url  = 'http://www.facebook.com/sharer.php?s=100';
                url += '&p[title]='     + encodeURIComponent(data.title);
                url += '&p[summary]='   + encodeURIComponent(data.text);
                url += '&p[url]='       + encodeURIComponent(data.url);
                url += '&p[images][0]=' + encodeURIComponent(data.img);
            share.popup($this, url, data.id, 'f');
        };
        return false;
    },
    data: function($this){
        if($this){
            return $.parseJSON($this.parent("div").attr("data-share-data"));
        };
        return false;
    },
    popup: function(box, url, id, type){
        var countBox = box.find(".count");
        if(!countBox.length){
            share.setCountLikes(box, 1);
        }else{
            countBox.text(parseInt(countBox.text()) + 1);
        };
        if (type == 't') {
            $.post('/netcat/modules/default/ajax.php?isNaked=1', {action: 'set_tw_cnt', page: id}, function (data){});
        }
        if (type == 'f') {
            $.post('/netcat/modules/default/ajax.php?isNaked=1', {action: 'set_fb_cnt', page: id}, function (data){});
        }
        if (type == 'v') {
            $.post('/netcat/modules/default/ajax.php?isNaked=1', {action: 'set_vk_cnt', page: id}, function (data){});
        }
        window.open(url, "", "toolbar=0, status=0, width=626, height=436");
        return false;
    },
};