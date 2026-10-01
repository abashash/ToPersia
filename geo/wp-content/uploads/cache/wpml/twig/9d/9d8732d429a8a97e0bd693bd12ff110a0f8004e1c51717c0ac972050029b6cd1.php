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

/* media-translation-table-row.twig */
class __TwigTemplate_050886dd5a21eadbb89a2983204055b5641028d9b048faea92de28ea4dd04170 extends \WPML\Core\Twig\Template
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
        echo "<tr class=\"wpml-media-attachment-row\" data-attachment-id=\"";
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "post", []), "ID", []), "html", null, true);
        echo "\"
\tdata-language-code=\"";
        // line 2
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["attachment"] ?? null), "language", []), "html", null, true);
        echo "\"
\tdata-language-name=\"";
        // line 3
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["languages"] ?? null), $this->getAttribute(($context["attachment"] ?? null), "language", []), [], "array"), "name", []), "html", null, true);
        echo "\"
\tdata-is-image=\"";
        // line 4
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["attachment"] ?? null), "is_image", []), "html", null, true);
        echo "\"
\tdata-thumb=\"";
        // line 5
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "thumb", []), "src", []), "html", null, true);
        echo "\"
\tdata-file-name=\"";
        // line 6
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["attachment"] ?? null), "file_name", []), "html", null, true);
        echo "\"
\tdata-mime-type=\"";
        // line 7
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["attachment"] ?? null), "mime_type", []), "html", null, true);
        echo "\"
\tdata-title=\"";
        // line 8
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "post", []), "post_title", []), "html", null, true);
        echo "\"
\tdata-caption=\"";
        // line 9
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "post", []), "post_excerpt", []), "html", null, true);
        echo "\"
\tdata-alt_text=\"";
        // line 10
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["attachment"] ?? null), "alt", []), "html", null, true);
        echo "\"
\tdata-description=\"";
        // line 11
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "post", []), "post_content", []), "html", null, true);
        echo "\"
\tdata-flag=\"";
        // line 12
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["languages"] ?? null), $this->getAttribute(($context["attachment"] ?? null), "language", []), [], "array"), "flag", []), "html", null, true);
        echo "\"
\tdata-custom_fields=\"";
        // line 13
        echo \WPML\Core\twig_escape_filter($this->env, \WPML\Core\twig_jsonencode_filter($this->getAttribute(($context["attachment"] ?? null), "custom_fields", [])), "html", null, true);
        echo "\"
\tdata-copy_from_original=\"";
        // line 14
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "copy_from_original", []), "html", null, true);
        echo "\"
\tdata-copied-media-label=\"";
        // line 15
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "copied_media", []), "html", null, true);
        echo "\"
\tdata-referenced-media-label=\"";
        // line 16
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "referenced_media", []), "html", null, true);
        echo "\"
\tdata-has-usages-in-posts-by-copy=\"";
        // line 17
        if ($this->getAttribute(($context["attachment"] ?? null), "has_usages_in_posts_by_copy", [])) {
            echo "1";
        } else {
            echo "0";
        }
        echo "\"
\tdata-has-usages-in-posts-by-reference=\"";
        // line 18
        if ($this->getAttribute(($context["attachment"] ?? null), "has_usages_in_posts_by_reference", [])) {
            echo "1";
        } else {
            echo "0";
        }
        echo "\">
\t<td class=\"wpml-col-media-title\">
        <span title=\"";
        // line 20
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["languages"] ?? null), $this->getAttribute(($context["attachment"] ?? null), "language", []), [], "array"), "name", []), "html", null, true);
        echo "\" class=\"wpml-media-original-flag js-otgs-popover-tooltip\"
\t\t\t  data-tippy-distance=\"-12\">
            <img src=\"";
        // line 22
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["languages"] ?? null), $this->getAttribute(($context["attachment"] ?? null), "language", []), [], "array"), "flag", []), "html", null, true);
        echo "\" width=\"16\" height=\"12\" alt=\"";
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["attachment"] ?? null), "language", []), "html", null, true);
        echo "\">
        </span>
\t\t<span class=\"wpml-media-wrapper\">
            <img src=\"";
        // line 25
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "thumb", []), "src", []), "html", null, true);
        echo "\"
\t\t\t\t width=\"";
        // line 26
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "thumb", []), "width", []), "html", null, true);
        echo "\"
\t\t\t\t height=\"";
        // line 27
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "thumb", []), "height", []), "html", null, true);
        echo "\"
\t\t\t\t alt=\"";
        // line 28
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["attachment"] ?? null), "language", []), "html", null, true);
        echo "\"
\t\t\t\t ";
        // line 29
        if ( !$this->getAttribute(($context["attachment"] ?? null), "is_image", [])) {
            // line 30
            echo "\t\t\t\t\t class=\"is-non-image\"
\t\t\t\t ";
        } else {
            // line 32
            echo "\t\t\t\t\t data-tippy-boundary=\"viewport\"
\t\t\t\t\t data-tippy-flip=\"true\"
\t\t\t\t\t data-tippy-placement=\"right\"
\t\t\t\t\t data-tippy-maxWidth= \"";
            // line 35
            echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "preview", []), "width", []), "html", null, true);
            echo "px\"
\t\t\t\t\t data-tippy-content=\"";
            // line 36
            echo \WPML\Core\twig_escape_filter($this->env, (((("<img style=\"max-width:100%;width:auto;max-height:" . $this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "preview", []), "height", [])) . "px;height:auto;\" src=\"") . $this->getAttribute(($context["attachment"] ?? null), "url", [])) . "\" />"), "html", null, true);
            echo "\"
\t\t\t\t\t class=\"js-otgs-popover-tooltip\"
\t\t\t\t ";
        }
        // line 39
        echo "\t\t\t>
        </span>
\t\t<span class=\"wpml-col-media-filename\">
\t\t\t";
        // line 42
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["attachment"] ?? null), "file_name", []), "html", null, true);
        echo "
\t\t</span>
\t</td>
\t";
        // line 45
        if (($context["should_handle_media_auto"] ?? null)) {
            // line 46
            echo "\t<td class=\"wpml-col-media-title\" style=\"vertical-align: middle\">
\t\t";
            // line 47
            if ($this->getAttribute(($context["attachment"] ?? null), "has_usages_in_posts_by_copy", [])) {
                // line 48
                echo "\t\t<div class=\"wpml-media-type-label\">";
                echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "copied_media", []), "html", null, true);
                echo "</div>
\t\t";
            }
            // line 50
            echo "\t\t";
            if (($this->getAttribute(($context["attachment"] ?? null), "has_usages_in_posts_by_copy", []) && $this->getAttribute(($context["attachment"] ?? null), "has_usages_in_posts_by_reference", []))) {
                // line 51
                echo "\t\t<div class=\"vertical-separator\"></div>
\t\t";
            }
            // line 53
            echo "\t\t";
            if ($this->getAttribute(($context["attachment"] ?? null), "has_usages_in_posts_by_reference", [])) {
                // line 54
                echo "\t\t<div class=\"wpml-media-type-label wpml-media-type-label--light\">";
                echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "referenced_media", []), "html", null, true);
                echo "</div>
\t\t";
            }
            // line 56
            echo "\t</td>
\t";
        }
        // line 58
        echo "\t<td class=\"wpml-col-media-translations\">
\t\t";
        // line 59
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["languages"] ?? null));
        foreach ($context['_seq'] as $context["code"] => $context["language"]) {
            // line 60
            echo "\t\t\t";
            if ((twig_test_empty(($context["target_language"] ?? null)) || (($context["target_language"] ?? null) == $context["code"]))) {
                // line 61
                echo "\t\t\t\t";
                if (($this->getAttribute(($context["attachment"] ?? null), "language", []) == $context["code"])) {
                    // line 62
                    echo "\t\t\t\t\t<span class=\"js-otgs-popover-tooltip\" data-tippy-distance=\"-12\"
\t\t\t\t\t\t  title=\"";
                    // line 63
                    echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["languages"] ?? null), $this->getAttribute(($context["attachment"] ?? null), "language", []), [], "array"), "name", []), "html", null, true);
                    echo ": ";
                    echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "original_language", []), "html", null, true);
                    echo "\">
                                    <i class=\"otgs-ico-original\"></i>
                                </span>
\t\t\t\t";
                } else {
                    // line 67
                    echo "\t\t\t\t\t<span class=\"wpml-media-wrapper js-otgs-popover-tooltip\"
\t\t\t\t\t\t  id=\"media-attachment-";
                    // line 68
                    echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "post", []), "ID", []), "html", null, true);
                    echo "-";
                    echo \WPML\Core\twig_escape_filter($this->env, $context["code"], "html", null, true);
                    echo "\"
\t\t\t\t\t\t  data-file-name=\"";
                    // line 69
                    echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "translations", []), $context["code"], [], "array"), "file_name", []), "html", null, true);
                    echo "\"
\t\t\t\t\t\t  title=\"";
                    // line 70
                    echo \WPML\Core\twig_escape_filter($this->env, sprintf($this->getAttribute(($context["strings"] ?? null), "edit_translation", []), $this->getAttribute($this->getAttribute(($context["languages"] ?? null), $context["code"], [], "array"), "name", [])), "html", null, true);
                    echo "\"
                            ";
                    // line 71
                    if ( !$this->getAttribute($this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "translations", []), $context["code"], [], "array"), "media_is_translated", [])) {
                        // line 72
                        echo "\t\t\t\t\t\t\t\tdata-tippy-distance=\"-12\"
\t\t\t\t\t\t\t";
                    }
                    // line 74
                    echo "                          data-attachment-id=\"";
                    echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "translations", []), $context["code"], [], "array"), "id", []), "html", null, true);
                    echo "\"
\t\t\t\t\t\t  data-language-code=\"";
                    // line 75
                    echo \WPML\Core\twig_escape_filter($this->env, $context["code"], "html", null, true);
                    echo "\"
\t\t\t\t\t\t  data-language-name=\"";
                    // line 76
                    echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($context["language"], "name", []), "html", null, true);
                    echo "\"
\t\t\t\t\t\t  data-url=\"";
                    // line 77
                    echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["attachment"] ?? null), "url", []), "html", null, true);
                    echo "\"
\t\t\t\t\t\t  data-thumb=\"";
                    // line 78
                    echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "translations", []), $context["code"], [], "array"), "thumb", []), "src", []), "html", null, true);
                    echo "\"
\t\t\t\t\t\t  data-title=\"";
                    // line 79
                    echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "translations", []), $context["code"], [], "array"), "title", []), "html", null, true);
                    echo "\"
\t\t\t\t\t\t  data-caption=\"";
                    // line 80
                    echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "translations", []), $context["code"], [], "array"), "caption", []), "html", null, true);
                    echo "\"
\t\t\t\t\t\t  data-alt_text=\"";
                    // line 81
                    echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "translations", []), $context["code"], [], "array"), "alt", []), "html", null, true);
                    echo "\"
\t\t\t\t\t\t  data-description=\"";
                    // line 82
                    echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "translations", []), $context["code"], [], "array"), "description", []), "html", null, true);
                    echo "\"
\t\t\t\t\t\t  data-flag=\"";
                    // line 83
                    echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["languages"] ?? null), $context["code"], [], "array"), "flag", []), "html", null, true);
                    echo "\"
\t\t\t\t\t\t  data-media-is-translated=\"";
                    // line 84
                    echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "translations", []), $context["code"], [], "array"), "media_is_translated", []), "html", null, true);
                    echo "\"
\t\t\t\t\t\t  data-custom_fields=\"";
                    // line 85
                    echo \WPML\Core\twig_escape_filter($this->env, \WPML\Core\twig_jsonencode_filter($this->getAttribute($this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "translations", []), $context["code"], [], "array"), "custom_fields", [])), "html", null, true);
                    echo "\"
\t\t\t\t\t>
                                    <a class=\"js-open-media-translation-dialog ";
                    // line 87
                    if ($this->getAttribute($this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "translations", []), $context["code"], [], "array"), "media_is_translated", [])) {
                        echo "wpml-media-translation-image";
                    }
                    echo "\">
                                        <img src=\"";
                    // line 88
                    echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "translations", []), $context["code"], [], "array"), "thumb", []), "src", []), "html", null, true);
                    echo "\"
\t\t\t\t\t\t\t\t\t\t\t width=\"";
                    // line 89
                    echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "thumb", []), "width", []), "html", null, true);
                    echo "\" height=\"";
                    echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "thumb", []), "height", []), "html", null, true);
                    echo "\"
\t\t\t\t\t\t\t\t\t\t\t alt=\"";
                    // line 90
                    echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["attachment"] ?? null), "language", []), "html", null, true);
                    echo "\"
\t\t\t\t\t\t\t\t\t\t\t ";
                    // line 91
                    if ( !$this->getAttribute(($context["attachment"] ?? null), "is_image", [])) {
                        echo "class=\"is-non-image\"";
                    }
                    // line 92
                    echo "\t\t\t\t\t\t\t\t\t\t\t";
                    if ( !$this->getAttribute($this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "translations", []), $context["code"], [], "array"), "media_is_translated", [])) {
                        echo "style=\"display:none\"";
                    }
                    echo ">
                                        <i class=\"";
                    // line 93
                    if ($this->getAttribute($this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "translations", []), $context["code"], [], "array"), "id", [])) {
                        echo "otgs-ico-edit";
                    } else {
                        echo "otgs-ico-add";
                    }
                    echo "\"
\t\t\t\t\t\t\t\t\t\t   ";
                    // line 94
                    if ($this->getAttribute($this->getAttribute($this->getAttribute(($context["attachment"] ?? null), "translations", []), $context["code"], [], "array"), "media_is_translated", [])) {
                        echo "style=\"display:none\"";
                    }
                    echo "></i>
                                    </a>
                                </span>
\t\t\t\t";
                }
                // line 98
                echo "\t\t\t";
            }
            // line 99
            echo "\t\t";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['code'], $context['language'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 100
        echo "\t</td>
</tr>
";
    }

    public function getTemplateName()
    {
        return "media-translation-table-row.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  363 => 100,  357 => 99,  354 => 98,  345 => 94,  337 => 93,  330 => 92,  326 => 91,  322 => 90,  316 => 89,  312 => 88,  306 => 87,  301 => 85,  297 => 84,  293 => 83,  289 => 82,  285 => 81,  281 => 80,  277 => 79,  273 => 78,  269 => 77,  265 => 76,  261 => 75,  256 => 74,  252 => 72,  250 => 71,  246 => 70,  242 => 69,  236 => 68,  233 => 67,  224 => 63,  221 => 62,  218 => 61,  215 => 60,  211 => 59,  208 => 58,  204 => 56,  198 => 54,  195 => 53,  191 => 51,  188 => 50,  182 => 48,  180 => 47,  177 => 46,  175 => 45,  169 => 42,  164 => 39,  158 => 36,  154 => 35,  149 => 32,  145 => 30,  143 => 29,  139 => 28,  135 => 27,  131 => 26,  127 => 25,  119 => 22,  114 => 20,  105 => 18,  97 => 17,  93 => 16,  89 => 15,  85 => 14,  81 => 13,  77 => 12,  73 => 11,  69 => 10,  65 => 9,  61 => 8,  57 => 7,  53 => 6,  49 => 5,  45 => 4,  41 => 3,  37 => 2,  32 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("", "media-translation-table-row.twig", "/home/topersia/public_html/geo/wp-content/plugins/wpml-media-translation/templates/menus/media-translation-table-row.twig");
    }
}
