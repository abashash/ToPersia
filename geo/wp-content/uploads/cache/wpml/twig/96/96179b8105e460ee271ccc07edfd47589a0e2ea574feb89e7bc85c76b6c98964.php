<?php

namespace WPML\Core;

use \WPML\Core\Twig\Environment;
use \WPML\Core\Twig\Error\LoaderError;
use \WPML\Core\Twig\Error\RuntimeError;
use \WPML\Core\Twig\Markup;
use \WPML\Core\Twig\Sandbox\SecurityError;
use \WPML\Core\Twig\Sandbox\SecurityNotAllowedTagError;
use \WPML\Core\Twig\Sandbox\SecurityNotAllowedFilterError;
use \WPML\Core\Twig\Sandbox\SecurityNotAllowedFunctionError;
use \WPML\Core\Twig\Source;
use \WPML\Core\Twig\Template;

/* media-translation-popup.twig */
class __TwigTemplate_32eed91a0d18ba6fd28685b308560e1568e9eb2b813877615424e0333888720e extends \WPML\Core\Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        // line 1
        echo "<div id=\"wpml-media-dialog\" hidden>
\t<div class=\"wpml-settings-list\" id=\"wpml-media-is-copy-notice\" style=\"display: none\">
\t\t<div role=\"presentation\">
\t\t\t<div class=\"warning notice-warning otgs-notice wpml-settings-list-notice\" style=\"display: block;\">
\t\t\t\t<h4 class=\"small-margin-top small-margin-bottom\">";
        // line 5
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "media_text_is_copy", []), "html", null, true);
        echo "</h4>
\t\t\t\t<p>";
        // line 6
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "media_text_is_copy_notice", []), "html", null, true);
        echo "</p>
\t\t\t\t<h4 id=\"wpml-media-is-copy-cannot-edit-notice\" class=\"small-margin-bottom\" style=\"display: none\"><i>";
        // line 7
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "media_text_cannot_edit_notice", []), "html", null, true);
        echo "</i></h4>
\t\t\t</div>
\t\t</div>
\t</div>
\t<div class=\"wpml-settings-list\" id=\"wpml-media-is-reference-notice\" style=\"display: none\">
\t\t<div role=\"presentation\">
\t\t\t<div class=\"warning notice-warning otgs-notice wpml-settings-list-notice\" style=\"display: block;\">
\t\t\t\t<h4 class=\"small-margin-top small-margin-bottom\">";
        // line 14
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "media_text_is_reference", []), "html", null, true);
        echo "</h4>
\t\t\t\t<p>";
        // line 15
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "media_text_is_reference_notice", []), "html", null, true);
        echo "</p>
\t\t\t\t<h4 class=\"small-margin-bottom\" style=\"display: none\"><i>";
        // line 16
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "media_text_cannot_edit_notice", []), "html", null, true);
        echo "</i></h4>
\t\t\t</div>
\t\t</div>
\t</div>
\t<div class=\"wpml-settings-list\" id=\"wpml-media-is-copyandreference-notice\" style=\"display: none\">
\t\t<div role=\"presentation\">
\t\t\t<div class=\"warning notice-warning otgs-notice wpml-settings-list-notice\" style=\"display: block;\">
\t\t\t\t<h4 class=\"small-margin-top small-margin-bottom\">";
        // line 23
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "media_text_is_copy_ref", []), "html", null, true);
        echo "</h4>
\t\t\t\t<p>";
        // line 24
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "media_text_is_copy_ref_notice", []), "html", null, true);
        echo "</p>
\t\t\t\t<h4 class=\"small-margin-bottom\" style=\"display: none\"><i>";
        // line 25
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "media_text_cannot_edit_notice", []), "html", null, true);
        echo "</i></h4>
\t\t\t</div>
\t\t</div>
\t</div>
\t<span class=\"ajax-loader\"></span>
    <form id=\"wpml-media-dialog-form\" method=\"post\" enctype=\"multipart/form-data\">
        ";
        // line 31
        echo ($context["nonce"] ?? null);
        echo "
\t\t<input type=\"hidden\" name=\"copied_media_label\" value=\"\"/>
\t\t<input type=\"hidden\" name=\"referenced_media_label\" value=\"\"/>
\t\t<input type=\"hidden\" name=\"has_usages_in_posts_by_copy\" value=\"\"/>
\t\t<input type=\"hidden\" name=\"has_usages_in_posts_by_reference\" value=\"\"/>
        <input type=\"hidden\" name=\"original-attachment-id\" value=\"\"/>
        <input type=\"hidden\" name=\"translated-attachment-id\" value=\"\"/>
        <input type=\"hidden\" name=\"translated-language\" value=\"\"/>
        <input type=\"hidden\" name=\"restore-media\" value=\"0\"/>
        <input type=\"hidden\" name=\"update-media-file\" value=\"0\"/>
        <input type=\"hidden\" name=\"action\" value=\"wpml_media_save_translation\"/>

\t\t<div class=\"wpml-form-row wpml-form-row--columns\">
\t\t\t<label></label>
\t\t\t<h3 class=\"wpml-header-original\">
\t\t\t\t";
        // line 46
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "original", []), "html", null, true);
        echo "
\t\t\t\t<div class=\"wpml-flag-wrapper\"><span class=\"wpml-title-flag\"><img src=\"#\"></span><strong>%from_language%</strong></div>
\t\t\t</h3>
\t\t\t<h3 class=\"wpml-header-translation\">
\t\t\t\t";
        // line 50
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "translation", []), "html", null, true);
        echo "
\t\t\t\t<div class=\"wpml-flag-wrapper\"><span class=\"wpml-title-flag\"><img src=\"#\"></span><strong>%to_language%</strong></div>
\t\t\t</h3>
\t\t</div>

        <div class=\"wpml-form-row wpml-form-row--columns\">

            <label>";
        // line 57
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "file", []), "html", null, true);
        echo "</label>

            <span class=\"wpml-media-wrapper\">
                <span class=\"wpml-media-original-image\">
                    <img src=\"#\" alt=\"#\">
                    <span class=\"wpml-media-original-title\"></span>
                </span>
            </span>

            <span class=\"wpml-media-wrapper wpml-media-upload-handle\">
                <a class=\"wpml-media-translation-image drag-drop-area\">
                    <img src=\"#\" alt=\"#\">
                    <span class=\"wpml-media-translated-title\"></span>
                    <span class=\"wpml-media-upload-text hidden\">";
        // line 70
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "use_different_file", []), "html", null, true);
        echo "</span>
                </a>

                <span class=\"js-wpml-media-revert wpml-display-block text-center hidden\">
                    <a class=\"button button-secondary button-small\">";
        // line 74
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "revert_to_original", []), "html", null, true);
        echo "</a>
                </span>
            </span>

        </div>

\t\t<div style=\"width: 100%; height: 20px\"></div>

        <div class=\"wpml-form-row wpml-form-row-title wpml-form-row-translation-field hidden\">
            <label for=\"media-title\">";
        // line 83
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "name", []), "html", null, true);
        echo "</label>
            <input readonly value=\"\" id=\"media-title-original\" type=\"text\">
\t\t\t<div class=\"wpml-row-button-row\">
\t\t\t\t<button class=\"button-copy button-secondary js-button-copy otgs-ico-copy\"
\t\t\t\t\t\ttitle=\"";
        // line 87
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "copy_from_original", []), "html", null, true);
        echo "\"
\t\t\t\t\t\tdata-defaultitle=\"";
        // line 88
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "copy_from_original", []), "html", null, true);
        echo "\"
\t\t\t\t\t\tdata-disabledtitle=\"";
        // line 89
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "media_text_cannot_edit_notice", []), "html", null, true);
        echo "\"></button>
\t\t\t</div>
            <input name=\"translation[title]\" id=\"media-title-translation\" value=\"\" type=\"text\">
        </div>
        <div class=\"wpml-form-row wpml-form-row-caption wpml-form-row-translation-field hidden\">
            <label for=\"media-caption\">";
        // line 94
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "caption", []), "html", null, true);
        echo "</label>
            <textarea readonly id=\"media-caption-original\" cols=\"22\" rows=\"4\"></textarea>
\t\t\t<div class=\"wpml-row-button-row\">
\t\t\t\t<button class=\"button-copy button-secondary js-button-copy otgs-ico-copy\"
\t\t\t\t\t\ttitle=\"";
        // line 98
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "copy_from_original", []), "html", null, true);
        echo "\"></button>
\t\t\t</div>
            <textarea name=\"translation[caption]\" id=\"media-caption-translation\" cols=\"22\" rows=\"4\"></textarea>
        </div>
        <div class=\"wpml-form-row wpml-form-row-alt-text wpml-form-row-translation-field hidden\">
            <label for=\"media-alt-text\">";
        // line 103
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "alt_text", []), "html", null, true);
        echo "</label>
            <input readonly value=\"\" id=\"media-alt-text-original\" type=\"text\">
\t\t\t<div class=\"wpml-row-button-row\">
\t\t\t\t<button class=\"button-copy button-secondary js-button-copy otgs-ico-copy\"
\t\t\t\t\t\ttitle=\"";
        // line 107
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "copy_from_original", []), "html", null, true);
        echo "\"></button>
\t\t\t</div>
            <input name=\"translation[alt-text]\" id=\"media-alt-text-translation\" value=\"\" type=\"text\">
        </div>
        <div class=\"wpml-form-row wpml-form-row-description wpml-form-row-translation-field hidden\">
            <label for=\"media-description\">";
        // line 112
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "description", []), "html", null, true);
        echo "</label>
            <textarea readonly id=\"media-description-original\" cols=\"22\" rows=\"4\"></textarea>
\t\t\t<div class=\"wpml-row-button-row\">
\t\t\t\t<button class=\"button-copy button-secondary js-button-copy otgs-ico-copy\"
\t\t\t\t\t\ttitle=\"";
        // line 116
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "copy_from_original", []), "html", null, true);
        echo "\"></button>
\t\t\t</div>
            <textarea name=\"translation[description]\" id=\"media-description-translation\" cols=\"22\" rows=\"4\"></textarea>
        </div>
\t\t<div id=\"wpml-custom-fields-container\"></div>
    </form>
    ";
        // line 122
        if ((false && ($context["show_text_change_notice"] ?? null))) {
            // line 123
            echo "    <div class=\"text-change-notice notice notice-info inline is-dismissible hidden\">
        <p>";
            // line 124
            echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "texts_change_notice", []), "html", null, true);
            echo "</p>
    </div>
    ";
        }
        // line 127
        echo "    <div id=\"wpml-media-upload-progress-animation\">
        <div class=\"upload-progress-bar\"></div>
        <div class=\"status\">0%</div>
    </div>
    <span id=\"wpml-media-upload-error\" class=\"icl_error_text\"></span>
</div>
<form id=\"wpml-media-file-upload-form\" enctype=\"multipart/form-data\">
    <input type=\"hidden\" name=\"action\" value=\"wpml_media_upload_file\">
    <input type=\"hidden\" name=\"attachment-id\" value=\"\">
    <input type=\"hidden\" name=\"original-attachment-id\" value=\"\">
    <input type=\"hidden\" name=\"language\" value=\"\">
    ";
        // line 138
        echo ($context["nonce"] ?? null);
        echo "
    <input style=\"display:none\" type=\"file\" name=\"image\" accept=\"image/*\" />
</form>
";
    }

    public function getTemplateName()
    {
        return "media-translation-popup.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  254 => 138,  241 => 127,  235 => 124,  232 => 123,  230 => 122,  221 => 116,  214 => 112,  206 => 107,  199 => 103,  191 => 98,  184 => 94,  176 => 89,  172 => 88,  168 => 87,  161 => 83,  149 => 74,  142 => 70,  126 => 57,  116 => 50,  109 => 46,  91 => 31,  82 => 25,  78 => 24,  74 => 23,  64 => 16,  60 => 15,  56 => 14,  46 => 7,  42 => 6,  38 => 5,  32 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("", "media-translation-popup.twig", "/home/topersia/public_html/geo/wp-content/plugins/wpml-media-translation/templates/menus/media-translation-popup.twig");
    }
}
