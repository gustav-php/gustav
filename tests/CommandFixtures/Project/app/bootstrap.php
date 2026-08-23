<?php

use GustavPHP\Gustav\{Configuration, Mode};

return new Configuration(
    mode: Mode::Production,
    namespace: 'GustavPHP\\Tests\\CommandFixtures\\ValidApplication',
    cache: sys_get_temp_dir(),
);
