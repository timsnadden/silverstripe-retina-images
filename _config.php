<?php

Object::useCustomClass('Image', 'RetinaImage'); 
Object::useCustomClass('Image_Cached', 'RetinaImage_Cached');

RetinaImage::set_use_retina_images();
