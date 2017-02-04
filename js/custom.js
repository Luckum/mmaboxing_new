$(document).ready(function() {
    $("#send_file").change(function() {
        HandleChanges();
    });
    $("#send_material_frm").submit(function() {
        var form = $(this);
        var error = false;
        
        form.find('input, textarea').each(function() {
            if (this.id !== 'send_file') {
                if ($(this).val() == '') {
                    $(this).addClass("error");
                    $(this).css({"background-color": "#f9e3e6", "color": "#d63d18", "border": "1px solid #d63d18"});
                    error = true;
                }
            }
        });
        
        if (!error) {
            var form_d = document.forms.send_material_frm,
                formData = new FormData(form_d),
                xhr = new XMLHttpRequest();

            xhr.open("POST", "/netcat/custom/ajax.php");
      
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4) {
                    if (xhr.status == 200) {
                        $("#send_material_frm").html('Спасибо за отправленный материал! Наши редактора проверят текст и опубликуют его на сайте');
                    }
                }
            };
            xhr.send(formData);
        }
        
        return false;
    });
    $(".send_input, .send_textarea").on("input", function(){
        if ($(this).val().length > 0 && $(this).hasClass("error")) {
            $(this).removeClass("error");
            $(this).css({"background-color": "#f7f7f7", "color": "#aaaaaa", "border": "1px solid #DDD"});
        } else if ($(this).val().length == 0) {
            $(this).addClass("error");
            $(this).css({"background-color": "#f9e3e6", "color": "#d63d18", "border": "1px solid #d63d18"});
        }
    });
});

function HandleChanges() {
    var file = $("#send_file").val();
    var reWin = /.*\\(.*)/;
    var fileTitle = file.replace(reWin, "$1");
    var reUnix = /.*\/(.*)/;
    fileTitle = fileTitle.replace(reUnix, "$1");
    $("#file_name").html(fileTitle);

    var RegExExt = /.*\.(.*)/;
    var ext = fileTitle.replace(RegExExt, "$1");

    var pos;
    if (ext) {
        switch (ext.toLowerCase()) {
            case 'doc': pos = '0'; break;
            case 'docx': pos = '0'; break;
            case 'bmp': pos = '16'; break;
            case 'jpg': pos = '32'; break;
            case 'jpeg': pos = '32'; break;
            case 'png': pos = '48'; break;
            case 'gif': pos = '64'; break;
            case 'psd': pos = '80'; break;
            case 'mp3': pos = '96'; break;
            case 'wav': pos = '96'; break;
            case 'ogg': pos = '96'; break;
            case 'avi': pos = '112'; break;
            case 'wmv': pos = '112'; break;
            case 'flv': pos = '112'; break;
            case 'pdf': pos = '128'; break;
            case 'exe': pos = '144'; break;
            case 'txt': pos = '160'; break;
            default: pos = '176'; break;
        };
        $("#file_name").show()
        $("#file_name").css({"background" : 'url(/images/files_types_icons.png) no-repeat 0 -'+pos+'px'});
    }
}