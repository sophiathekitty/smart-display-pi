# smart-display-pi
i'm building a smart display for the ecosystem of raspberry pi and other local web servers that i have running different webapp projects. like my smart nighlight project. which got merged with the main hub app which is currently a private git repo.

this project has the new mvc project organization. maybe someday i'll make it back into classes... but now it's just a bunch of functions.

i'm putting all the functions that directly talk to the database in models... and then anything that does stuff with that or links together a bunch of models i'm putting under modules. i have a function for outputting json under views. i have a fancy file under includes that will include all the files under modeles, models, and views. i then have an api folder where i'm putting in all the api files that actually use the modules, models, and views to do api stuff. and then i also have a helpers folder where i'm putting things i want to call with crontab.
