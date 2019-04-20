### 1.24.0 (2018-11-05)

  * Added a `ResettableInterface` in order to reset/reset/clear/flush handlers and processors
  * Fixed normalization of exception traces when call_user_func is used to avoid serializing objects and the data they contain
  * Fixed display of anonymous class names

### 1.23.0 (2017-06-19)

  * Improved SyslogUdpHandler's support for RFC5424 and added optional `$ident` argument
  * Fixed compatibility issue with PHP <5.3.6
  * Deprecated RotatingFileHandler::setFilenameFormat to only support 3 formats: Y, Y-m and Y-m-d
  * Fixed WhatFailureGroupHandler to work with PHP7 throwables
  * Fixed a few minor bugs

### 1.0.0-RC1 (2011-07-01)

  * Initial release
