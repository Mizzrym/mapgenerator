<?php

require __DIR__ . '/generator.php';

(new ImageGenerator((new MapGenerator())))->display();
