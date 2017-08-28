<?php

if(defined('webpack_requirements_backend_off')) {
    //do nothing
} else {
    Requirements::set_backend(new Requirements_Backend_For_Webpack());
}
