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

/* media-translation-filters.twig */
class __TwigTemplate_af87c74b58b84e5e5860e3aa71c60e4c64c321201d9689b377f4af37ac8694a5 extends \WPML\Core\Twig\Template
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
        echo "<form id=\"posts-filter\" method=\"get\">
    <input type=\"hidden\" name=\"page\" value=\"wpml-media\"/>
    <input type=\"hidden\" name=\"sm\" value=\"media-translation\"/>
    ";
        // line 4
        echo ($context["nonce"] ?? null);
        echo "
\t<div class=\"wpml-media-translation-header-col\">
\t\t<label for=\"filter-by-date\" class=\"screen-reader-text\">";
        // line 6
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "filter_by_date", []), "html", null, true);
        echo "</label>
\t\t<select id=\"filter-by-date\" name=\"m\">
\t\t\t<option ";
        // line 8
        if ((($context["selected_month"] ?? null) == 0)) {
            echo "selected=\"selected\"";
        }
        // line 9
        echo "\t\t\t\t\tvalue=\"0\">";
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "all_dates", []), "html", null, true);
        echo "</option>
\t\t\t";
        // line 10
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["months"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["month"]) {
            // line 11
            echo "\t\t\t\t<option ";
            if ((($context["selected_month"] ?? null) == $this->getAttribute($context["month"], "id", []))) {
                echo "selected=\"selected\"";
            }
            // line 12
            echo "\t\t\t\t\t\tvalue=\"";
            echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($context["month"], "id", []), "html", null, true);
            echo "\">";
            echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($context["month"], "label", []), "html", null, true);
            echo "</option>
\t\t\t";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['month'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 14
        echo "\t\t</select>
\t</div>
\t<div class=\"wpml-media-translation-header-col\">
    \t";
        // line 17
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "in", []), "html", null, true);
        echo "
\t</div>
\t<div class=\"wpml-media-translation-header-col\">
\t\t<label for=\"filter-by-language\" class=\"screen-reader-text\">";
        // line 20
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "filter_by_language", []), "html", null, true);
        echo "</label>
\t\t<select id=\"filter-by-language\" name=\"slang\">
\t\t\t<option value=\"\">";
        // line 22
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "any_language", []), "html", null, true);
        echo "</option>
\t\t\t";
        // line 23
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["languages"] ?? null));
        foreach ($context['_seq'] as $context["code"] => $context["language"]) {
            // line 24
            echo "\t\t\t\t<option ";
            if ((($context["from_language"] ?? null) == $context["code"])) {
                echo "selected=\"selected\"";
            }
            // line 25
            echo "\t\t\t\t\t\tvalue=\"";
            echo \WPML\Core\twig_escape_filter($this->env, $context["code"], "html", null, true);
            echo "\">";
            echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($context["language"], "name", []), "html", null, true);
            echo "</option>
\t\t\t";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['code'], $context['language'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 27
        echo "\t\t</select>
\t</div>
\t<div class=\"wpml-media-translation-header-col\">
    \t";
        // line 30
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "to", []), "html", null, true);
        echo "
\t</div>
\t<div class=\"wpml-media-translation-header-col\">
\t\t<select id=\"filter-by-language\" name=\"tlang\">
\t\t\t<option value=\"\">";
        // line 34
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "any_language", []), "html", null, true);
        echo "</option>
\t\t\t";
        // line 35
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["languages"] ?? null));
        foreach ($context['_seq'] as $context["code"] => $context["language"]) {
            // line 36
            echo "\t\t\t\t<option ";
            if ((($context["to_language"] ?? null) == $context["code"])) {
                echo "selected=\"selected\"";
            }
            // line 37
            echo "\t\t\t\t\t\tvalue=\"";
            echo \WPML\Core\twig_escape_filter($this->env, $context["code"], "html", null, true);
            echo "\">";
            echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($context["language"], "name", []), "html", null, true);
            echo "</option>
\t\t\t";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['code'], $context['language'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 39
        echo "\t\t</select>
\t</div>

\t<div class=\"wpml-media-translation-header-col\">
\t\t<label for=\"filter-by-translation-status\" class=\"screen-reader-text\">";
        // line 43
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "filter_by_status", []), "html", null, true);
        echo "</label>
\t\t<select id=\"filter-by-translation-status\" name=\"status\">
\t\t\t<option ";
        // line 45
        if ((\WPML\Core\twig_length_filter($this->env, ($context["selected_status"] ?? null)) == 0)) {
            echo "selected=\"selected\"";
        }
        // line 46
        echo "\t\t\t\t\tvalue=\"\">";
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "status_all", []), "html", null, true);
        echo "</option>
\t\t\t<option ";
        // line 47
        if (((($context["selected_status"] ?? null) == $this->getAttribute(($context["statuses"] ?? null), "not_translated", [])) && (\WPML\Core\twig_length_filter($this->env, ($context["selected_status"] ?? null)) > 0))) {
            echo "selected=\"selected\"";
        }
        // line 48
        echo "\t\t\t\t\tvalue=\"";
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["statuses"] ?? null), "not_translated", []), "html", null, true);
        echo "\">";
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "status_not", []), "html", null, true);
        echo "</option>
\t\t\t<option ";
        // line 49
        if (((($context["selected_status"] ?? null) == $this->getAttribute(($context["statuses"] ?? null), "in_progress", [])) && (\WPML\Core\twig_length_filter($this->env, ($context["selected_status"] ?? null)) > 0))) {
            echo "selected=\"selected\"";
        }
        // line 50
        echo "\t\t\t\t\tvalue=\"";
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["statuses"] ?? null), "in_progress", []), "html", null, true);
        echo "\">";
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "status_in_progress", []), "html", null, true);
        echo "</option>
\t\t\t<option ";
        // line 51
        if ((($context["selected_status"] ?? null) == $this->getAttribute(($context["statuses"] ?? null), "translated", []))) {
            echo "selected=\"selected\"";
        }
        // line 52
        echo "\t\t\t\t\tvalue=\"";
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["statuses"] ?? null), "translated", []), "html", null, true);
        echo "\">";
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "status_translated", []), "html", null, true);
        echo "</option>
\t\t\t<option ";
        // line 53
        if ((($context["selected_status"] ?? null) == $this->getAttribute(($context["statuses"] ?? null), "needs_translation", []))) {
            echo "selected=\"selected\"";
        }
        // line 54
        echo "\t\t\t\t\tvalue=\"";
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["statuses"] ?? null), "needs_translation", []), "html", null, true);
        echo "\">";
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "status_needs_translation", []), "html", null, true);
        echo "</option>
\t\t</select>
\t</div>

\t<div id=\"media-search-input-wrap\">
    \t<label class=\"screen-reader-text\" for=\"media-search-input\">";
        // line 59
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "search_media", []), "html", null, true);
        echo "</label>
    \t<input size=\"25\" type=\"search\" id=\"media-search-input\" placeholder=\"";
        // line 60
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "search_placeholder", []), "html", null, true);
        echo "\" name=\"s\"
           value=\"";
        // line 61
        echo \WPML\Core\twig_escape_filter($this->env, ($context["search"] ?? null), "html", null, true);
        echo "\">
\t</div>
    <input type=\"submit\" class=\"button action\" value=\"";
        // line 63
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "search_button_label", []), "html", null, true);
        echo "\">
</form>";
    }

    public function getTemplateName()
    {
        return "media-translation-filters.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  233 => 63,  228 => 61,  224 => 60,  220 => 59,  209 => 54,  205 => 53,  198 => 52,  194 => 51,  187 => 50,  183 => 49,  176 => 48,  172 => 47,  167 => 46,  163 => 45,  158 => 43,  152 => 39,  141 => 37,  136 => 36,  132 => 35,  128 => 34,  121 => 30,  116 => 27,  105 => 25,  100 => 24,  96 => 23,  92 => 22,  87 => 20,  81 => 17,  76 => 14,  65 => 12,  60 => 11,  56 => 10,  51 => 9,  47 => 8,  42 => 6,  37 => 4,  32 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("", "media-translation-filters.twig", "/home/topersia/public_html/geo/wp-content/plugins/wpml-media-translation/templates/menus/media-translation-filters.twig");
    }
}
