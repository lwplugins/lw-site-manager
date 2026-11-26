<?php
/**
 * Page-builder post meta keys (pure data).
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\Meta;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The post meta keys page builders keep their layout, caches and generated CSS in.
 *
 * Each key was read from the builder's own source (or from the LW Site Manager
 * Extra integration that writes it), never guessed. Builders that keep the
 * layout in post_content (Avada, Etch, Divi 5) only list the meta they keep
 * beside it. Pure data: the matching lives in BuilderMetaKeys.
 */
final class BuilderMetaKeyList {

    /**
     * Builder groups, as id => [ label, family, editor, keys, prefixes, patterns ].
     *
     * `family` names the dedicated LW Site Manager Extra ability family (none
     * for builders it only reads or does not support); `editor` the builder's
     * own editor, "the <label> editor" when left out. Keys and prefixes are matched
     * case-insensitively; patterns are regular expressions.
     *
     * @return array<string, array{label: string, family?: string, editor?: string, keys?: list<string>, prefixes?: list<string>, patterns?: list<string>}>
     */
    public static function groups(): array {
        return [
            'elementor'      => [
                'label'     => 'Elementor',
                'family'    => 'site-manager/el-*',
                'keys'      => [ '_elementor' ],
                'prefixes'  => [ '_elementor_' ],
            ],
            'bricks'         => [
                'label'     => 'Bricks',
                'family'    => 'site-manager/br-*',
                'prefixes'  => [ '_bricks_', '_bab_' ],
            ],
            'breakdance'     => [
                'label'     => 'Breakdance',
                'family'    => 'site-manager/bd-*',
                'prefixes'  => [ '_breakdance_' ],
            ],
            'oxygen'         => [
                'label'     => 'Oxygen',
                'family'    => 'site-manager/oxy-*',
                'prefixes'  => [ '_oxygen_' ],
            ],
            'beaver-builder' => [
                'label'     => 'Beaver Builder',
                'family'    => 'site-manager/beaver-*',
                'prefixes'  => [ '_fl_builder_' ],
            ],
            'wpbakery'       => [
                'label'     => 'WPBakery',
                'family'    => 'site-manager/wpb-*',
                'keys'      => [ '_vc_post_settings' ],
                'prefixes'  => [ '_wpb_' ],
            ],
            'divi'           => [
                'label'     => 'Divi',
                'editor'    => 'the Divi builder',
                'keys'      => [
                    '_et_pb_use_builder',
                    '_et_pb_old_content',
                    '_et_pb_built_for_post_type',
                    '_et_pb_static_css_file',
                    '_et_pb_custom_css',
                    '_et_dynamic_cached_shortcodes',
                    '_et_dynamic_cached_attributes',
                    'et_enqueued_post_fonts',
                ],
                'prefixes'  => [ '_et_builder_' ],
            ],
            'brizy'          => [
                'label'     => 'Brizy',
                'keys'      => [ 'brizy' ],
                'prefixes'  => [ 'brizy-', 'brizy_' ],
            ],
            'kirki'          => [
                'label'     => 'Kirki',
                'keys'      => [ 'kirki' ],
                'prefixes'  => [ 'kirki_' ],
                'patterns'  => [ '/^staged_\d+_kirki/i' ],
            ],
            'spectra'        => [
                'label'     => 'Spectra',
                'family'    => 'site-manager/spectra-*',
                'prefixes'  => [ '_uag_', '_uagb_' ],
            ],
            'generateblocks' => [
                'label'     => 'GenerateBlocks',
                'family'    => 'site-manager/gb-*',
                'keys'      => [ '_generateblocks_dynamic_css_version' ],
                'prefixes'  => [ 'gb_style_' ],
            ],
            'lw-snapshots'   => [
                'label'     => 'LW layout-snapshot',
                'family'    => 'site-manager/*-snapshot*',
                'editor'    => 'the builder\'s editor',
                'keys'      => [ '_lw_sme_layout_snapshots' ],
            ],
        ];
    }
}
