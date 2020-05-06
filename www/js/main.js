var weather_url = "http://api.openweathermap.org/data/2.5/weather?q=Westminster,US&units=imperial&appid=de70cab93f8df394687971295474e104"
var months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
var day_of_week = ["Sun", "Mon", "Tue", "Wed","Thu", "Fri", "Sat"];
var stndrdth = ["st","nd","rd","th","th","th","th","th","th","th","th","th","th","th","th","th","th","th","th","st","nd","rd","th","th","th","th","th","th","th","st"];
/*
8 888888888o.   8 8888888888            .8.          8 888888888o.    `8.`8888.      ,8' 
8 8888    `88.  8 8888                 .888.         8 8888    `^888.  `8.`8888.    ,8'  
8 8888     `88  8 8888                :88888.        8 8888        `88. `8.`8888.  ,8'   
8 8888     ,88  8 8888               . `88888.       8 8888         `88  `8.`8888.,8'    
8 8888.   ,88'  8 888888888888      .8. `88888.      8 8888          88   `8.`88888'     
8 888888888P'   8 8888             .8`8. `88888.     8 8888          88    `8. 8888      
8 8888`8b       8 8888            .8' `8. `88888.    8 8888         ,88     `8 8888      
8 8888 `8b.     8 8888           .8'   `8. `88888.   8 8888        ,88'      8 8888      
8 8888   `8b.   8 8888          .888888888. `88888.  8 8888    ,o88P'        8 8888      
8 8888     `88. 8 888888888888 .8'       `8. `88888. 8 888888888P'           8 8888      
*/
$(document).ready(function(){
    PullWeather();
    window.weatherTimer = setInterval(PullWeather,36000);
    DisplayTime();
    window.timeTimer = setInterval(DisplayTime,1000);
});

function DisplayTime(){
    var date = new Date(Date.now());
    console.log(date);
    var hours = date.getHours();
    var am = "am";
    if(hours == 12)
        am = "pm";
    if(hours > 12){
        hours -= 12;
        am = "pm";
    }
    if(hours == 0){
        hours = 12;
        am = "am";
    }
    var mins = date.getMinutes();
    if(mins < 10){
        mins = "0" + mins
    }
    var time = hours + ":" + mins + am;
    console.log(time);
    $("header [var=current_time]").html(time);
    $("header [var=current_time]").attr("time",time);
    $("header [var=current_date]").html(day_of_week[date.getDay()] + ", " + months[date.getMonth()] + " " + date.getDate() + stndrdth[date.getDate()-1] + ", " + date.getFullYear());
    $("header [var=current_date]").attr("date",date.getMonth() + "/" + date.getDate());
}

function PullWeather(){
    $.get(weather_url).done(function(weather){
        console.log(weather);

        $("#live_weather").attr("weather",weather.weather[0].description);
        $("#live_weather").attr("icon",weather.weather[0].icon);
        $("#live_weather [var=temp]").html(Math.round(weather.main.temp));
        $("#live_weather [var=temp]").attr("temp_range",Math.round(weather.main.temp/10));
        $("#live_weather [var=feels_like]").html(Math.round(weather.main.feels_like));
        $("#live_weather [var=feels_like]").attr("temp_range",Math.round(weather.main.feels_like/10));
        $("#live_weather [var=humidity]").html(Math.round(weather.main.humidity));
    });
}