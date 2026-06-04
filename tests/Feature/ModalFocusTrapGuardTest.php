<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Accessibility guard: every modal overlay in the app must carry the
 * v-focus-trap directive (Escape-to-close + focus trap + focus restore).
 *
 * A "modal overlay" is detected as a <div> with a v-if and a full-screen,
 * z-indexed layer (fixed inset-0 + z-40/z-50/z-[…]). Detection uses substring
 * checks (not a regex) so it stays correct even though wired closers contain
 * "=>" (e.g. v-focus-trap="() => (showModal = false)").
 *
 * Sibling to the other UI guards (NoEmoji / NoNativeConfirm / NoEnglishTitle /
 * NoUnlabeledIconButton). Scans ALL panels under resources/js/Pages.
 *
 * If a future full-screen layer is genuinely NOT a dismissible dialog (e.g. a
 * blocking loading veil), add v-focus-trap anyway (harmless) or drop its
 * z-40/z-50 class so it isn't a modal signature.
 */
class ModalFocusTrapGuardTest extends TestCase
{
    public function test_every_modal_overlay_has_focus_trap(): void
    {
        $dir = resource_path('js/Pages');
        $offenders = [];

        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($it as $file) {
            if (substr($file, -4) !== '.vue') {
                continue;
            }

            foreach (file($file->getPathname()) as $i => $line) {
                if (! $this->isModalOverlayLine($line)) {
                    continue;
                }
                if (strpos($line, 'v-focus-trap') !== false) {
                    continue;
                }
                $offenders[] = str_replace($dir.'/', '', $file->getPathname()).':'.($i + 1);
            }
        }

        $this->assertEmpty(
            $offenders,
            'These modal overlays lack v-focus-trap (Escape/focus-trap a11y) — add it on the panel/overlay div, '
            ."e.g. v-focus-trap=\"() => (showModal = false)\":\n  ".implode("\n  ", $offenders)
        );
    }

    private function isModalOverlayLine(string $line): bool
    {
        if (strpos($line, '<div') === false) {
            return false;
        }
        if (strpos($line, 'v-if="') === false) {
            return false;
        }
        if (strpos($line, 'fixed inset-0') === false) {
            return false;
        }

        // Require a modal-ish z-index so non-modal full-screen layers don't trip the guard.
        return strpos($line, 'z-4') !== false
            || strpos($line, 'z-5') !== false
            || strpos($line, 'z-[') !== false;
    }
}
