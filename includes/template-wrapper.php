<?php
/**
 * Ara şablon: asıl şablon KVKKC_Blocker::render_template() içinde tamponlanarak yüklenir.
 * Yalnızca çekirdeğin şablon çıktı tamponu kullanılamadığında devreye girer.
 *
 * @package KVKK_Cerez
 */

// Doğrudan erişimi engelle.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

KVKKC_Blocker::render_template();
