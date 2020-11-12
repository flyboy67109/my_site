$(function () {
    $('#portfolio button').click(function () {
        var filter = $(this).val();
        var url = "{{ path('gallery_gallery') }}/1/"+filter;
        $.get(url,function (data) {
            $("#gallery").html(data);
            $('html, body').animate({
                scrollTop: ($('#filters').offset().top)
            },500);
            //clear class on filter
            $("#filters button").removeClass('w3-black');
            $("#filters button").addClass('w3-white');
            console.log($(this));
            // set this class on filter
            $("#filter_"+filter).removeClass('w3-white');
            $("#filter_"+filter).addClass('w3-black');
        })
    })
})