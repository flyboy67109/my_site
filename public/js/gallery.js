$(function () {
    function pagination(url) {
        event.preventDefault();
        $.get(url,function (data) {
            $("#gallery").html(data);
            $('html, body').animate({
                scrollTop: ($('#filters').offset().top)
            },500);
        })
    }
})