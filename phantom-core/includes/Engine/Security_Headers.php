<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

defined('ABSPATH') || exit;

class Security_Headers {

  public function send(bool $is_customizer_preview = false): void {
    header('Content-Type: text/html; charset=UTF-8');

    if (!$is_customizer_preview && is_ssl()) {
      header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    if (!$is_customizer_preview) {
      $csp = "default-src 'self' https: data:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https: http: *.jsdelivr.net *.cdnjs.cloudflare.com *.googleapis.com *.gstatic.com; style-src 'self' 'unsafe-inline' https:; img-src 'self' https: data: blob:; font-src 'self' https: data:; connect-src 'self' https: http: ws: wss:; frame-src 'self' https:; media-src 'self' https:;";
    } else {
      $csp = "default-src 'self' https: data:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https: http: *.jsdelivr.net *.cdnjs.cloudflare.com *.googleapis.com *.gstatic.com; style-src 'self' 'unsafe-inline' https:; img-src 'self' https: data: blob:; font-src 'self' https: data:; connect-src 'self' https: http: ws: wss:; frame-src 'self' https:; media-src 'self' https:;";
    }

    $csp = apply_filters('phantom_csp_header', $csp);
    header("Content-Security-Policy: " . $csp);

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), interest-cohort=()');
  }
}
