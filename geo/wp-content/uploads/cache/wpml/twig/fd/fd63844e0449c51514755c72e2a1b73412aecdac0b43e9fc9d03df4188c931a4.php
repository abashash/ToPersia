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

/* media-translation.twig */
class __TwigTemplate_16dc0ff2b96954477d043757c50b9454b1d3acd3a801b78ed9668fd7fc2cddc7 extends \WPML\Core\Twig\Template
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
        echo "<div class=\"wrap\">

    <h2>";
        // line 3
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "heading", []), "html", null, true);
        echo "</h2>

    ";
        // line 5
        $this->loadTemplate("batch-translation.twig", "media-translation.twig", 5)->display(twig_array_merge($context, ($context["batch_translation"] ?? null)));
        // line 6
        echo "
    <div class=\"tablenav top wpml-media-tablenav\">
        ";
        // line 8
        $this->loadTemplate("media-translation-filters.twig", "media-translation.twig", 8)->display($context);
        // line 9
        echo "    </div>

    <table class=\"widefat striped wpml-media-table js-otgs-table-sticky-header\">
        <thead>
        <tr>
            <th class=\"wpml-col-media-title\">";
        // line 14
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "original_language", []), "html", null, true);
        echo "</th>
\t\t\t";
        // line 15
        if (($context["should_handle_media_auto"] ?? null)) {
            // line 16
            echo "\t\t\t<th class=\"wpml-col-media-title\">";
            echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "media_type", []), "html", null, true);
            echo "</th>
\t\t\t";
        }
        // line 18
        echo "            <th class=\"wpml-col-media-translations\">
                ";
        // line 19
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["languages"] ?? null));
        foreach ($context['_seq'] as $context["code"] => $context["language"]) {
            // line 20
            echo "                    ";
            if ((twig_test_empty(($context["target_language"] ?? null)) || (($context["target_language"] ?? null) == $context["code"]))) {
                // line 21
                echo "                        <span class=\"js-otgs-popover-tooltip\" title=\"";
                echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($context["language"], "name", []), "html", null, true);
                echo "\"><img src=\"";
                echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($context["language"], "flag", []), "html", null, true);
                echo "\"
                                                                                               width=\"16\" height=\"12\"
                                                                                               alt=\"";
                // line 23
                echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($context["language"], "code", []), "html", null, true);
                echo "\"></span>
                    ";
            }
            // line 25
            echo "                ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['code'], $context['language'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 26
        echo "            </th>
        </tr>
        </thead>
        <tbody>
        ";
        // line 30
        if (($context["attachments"] ?? null)) {
            // line 31
            echo "            ";
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["attachments"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["attachment"]) {
                // line 32
                echo "                ";
                $this->loadTemplate("media-translation-table-row.twig", "media-translation.twig", 32)->display(twig_to_array(["attachment" => $context["attachment"], "languages" => ($context["languages"] ?? null), "strings" => ($context["strings"] ?? null), "target_language" => ($context["target_language"] ?? null), "should_handle_media_auto" => ($context["should_handle_media_auto"] ?? null)]));
                // line 33
                echo "            ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['attachment'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 34
            echo "        ";
        } else {
            // line 35
            echo "            <tr>
                <td colspan=\"2\">";
            // line 36
            echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["strings"] ?? null), "no_attachments", []), "html", null, true);
            echo "</td>
            </tr>
        ";
        }
        // line 39
        echo "        </tbody>

    </table>

    <div class=\"tablenav bottom\">
        ";
        // line 44
        $this->loadTemplate("pagination.twig", "media-translation.twig", 44)->display(twig_to_array(["pagination_model" => ($context["pagination"] ?? null)]));
        // line 45
        echo "\t\t";
        $this->loadTemplate("media-translation-popup.twig", "media-translation.twig", 45)->display($context);
        // line 46
        echo "    </div>

</div>";
    }

    public function getTemplateName()
    {
        return "media-translation.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  143 => 46,  140 => 45,  138 => 44,  131 => 39,  125 => 36,  122 => 35,  119 => 34,  113 => 33,  110 => 32,  105 => 31,  103 => 30,  97 => 26,  91 => 25,  86 => 23,  78 => 21,  75 => 20,  71 => 19,  68 => 18,  62 => 16,  60 => 15,  56 => 14,  49 => 9,  47 => 8,  43 => 6,  41 => 5,  36 => 3,  32 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("", "media-translation.twig", "/home/topersia/public_html/geo/wp-content/plugins/wpml-media-translation/templates/menus/media-translation.twig");
    }
}
