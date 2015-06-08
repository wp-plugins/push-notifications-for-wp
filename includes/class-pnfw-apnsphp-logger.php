<?php

if (!defined('ABSPATH')) {
 exit; // Exit if accessed directly
}

class PNFW_ApnsPHP_Logger implements ApnsPHP_Log_Interface {
 public function log($sMessage) {
  pnfw_log(PNFW_SYSTEM_LOG, $sMessage);
 }
}
