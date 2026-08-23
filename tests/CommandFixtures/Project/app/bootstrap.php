<?php

use GustavPHP\Gustav\{Configuration, Mode};

return new Configuration(
    mode: Mode::Production,
    namespace: 'GustavPHP\\Tests\\CommandFixtures\\ValidApplication',
);
