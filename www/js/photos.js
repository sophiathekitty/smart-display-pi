function LoadPhoto(){
    clearTimeout(window.photoTimer);
    window.photoTimer = setTimeout(LoadPhoto,min_to_millisecond(window.settings.photo_refresh));
    $.get("/api/photos").done(function(photos){
        $("body").css({"background-image":"url("+encodeURI(photos.random.url)+")"});
    });
}