<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* database/data_dictionary/index.twig */
class __TwigTemplate_e46dad66fbc446ed467c8d7061f0a710 extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        echo "<div class=\"container-fluid\">
  <h1>";
        // line 2
        echo twig_escape_filter($this->env, ($context["database"] ?? null), "html", null, true);
        echo "</h1>
  ";
        // line 3
        if ( !twig_test_empty(($context["comment"] ?? null))) {
            // line 4
            echo "    <p>";
echo _gettext("Database comment:");
            echo " <em>";
            echo twig_escape_filter($this->env, ($context["comment"] ?? null), "html", null, true);
            echo "</em></p>
  ";
        }
        // line 6
        echo "
  <p class=\"d-print-none\">
    <button type=\"button\" class=\"btn btn-secondary jsPrintButton\">";
        // line 8
        echo PhpMyAdmin\Html\Generator::getIcon("b_print", _gettext("Print"), true);
        echo "</button>
  </p>

  <div>
    ";
        // line 12
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["tables"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["table"]) {
            // line 13
            echo "      <div>
        <h2>";
            // line 14
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["table"], "name", [], "any", false, false, false, 14), "html", null, true);
            echo "</h2>
        ";
            // line 15
            if ( !twig_test_empty(twig_get_attribute($this->env, $this->source, $context["table"], "comment", [], "any", false, false, false, 15))) {
                // line 16
                echo "          <p>";
echo _gettext("Table comments:");
                echo " <em>";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["table"], "comment", [], "any", false, false, false, 16), "html", null, true);
                echo "</em></p>
        ";
            }
            // line 18
            echo "
        <table class=\"table table-striped align-middle\">
          <thead>
            <tr>
              <th>";
echo _gettext("Column");
            // line 22
            echo "</th>
              <th>";
echo _gettext("Type");
            // line 23
            echo "</th>
              <th>";
echo _gettext("Null");
            // line 24
            echo "</th>
              <th>";
echo _gettext("Default");
            // line 25
            echo "</th>
              ";
            // line 26
            if (twig_get_attribute($this->env, $this->source, $context["table"], "has_relation", [], "any", false, false, false, 26)) {
                // line 27
                echo "                <th>";
echo _gettext("Links to");
                echo "</th>
              ";
            }
            // line 29
            echo "              <th>";
echo _gettext("Comments");
            echo "</th>
              ";
            // line 30
            if (twig_get_attribute($this->env, $this->source, $context["table"], "has_mime", [], "any", false, false, false, 30)) {
                // line 31
                echo "                <th>";
echo _gettext("Media type");
                echo "</th>
              ";
            }
            // line 33
            echo "            </tr>
          </thead>
          <tbody>
            ";
            // line 36
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, $context["table"], "columns", [], "any", false, false, false, 36));
            foreach ($context['_seq'] as $context["_key"] => $context["column"]) {
                // line 37
                echo "              <tr>
                <td class=\"text-nowrap\">
                  ";
                // line 39
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["column"], "name", [], "any", false, false, false, 39), "html", null, true);
                echo "
                  ";
                // line 40
                if (twig_get_attribute($this->env, $this->source, $context["column"], "has_primary_key", [], "any", false, false, false, 40)) {
                    // line 41
                    echo "                    <em>(";
echo _gettext("Primary");
                    echo ")</em>
                  ";
                }
                // line 43
                echo "                </td>
                <td lang=\"en\" dir=\"ltr\"";
                // line 44
                echo (((("set" != twig_get_attribute($this->env, $this->source, $context["column"], "type", [], "any", false, false, false, 44)) && ("enum" != twig_get_attribute($this->env, $this->source, $context["column"], "type", [], "any", false, false, false, 44)))) ? (" class=\"text-nowrap\"") : (""));
                echo ">
                  ";
                // line 45
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["column"], "print_type", [], "any", false, false, false, 45), "html", null, true);
                echo "
                </td>
                <td>";
                // line 47
                echo twig_escape_filter($this->env, ((twig_get_attribute($this->env, $this->source, $context["column"], "is_nullable", [], "any", false, false, false, 47)) ? (_gettext("Yes")) : (_gettext("No"))), "html", null, true);
                echo "</td>
                <td class=\"text-nowrap\">
                  ";
                // line 49
                if (((null === twig_get_attribute($this->env, $this->source, $context["column"], "default", [], "any", false, false, false, 49)) && twig_get_attribute($this->env, $this->source, $context["column"], "is_nullable", [], "any", false, false, false, 49))) {
                    // line 50
                    echo "                    <em>NULL</em>
                  ";
                } else {
                    // line 52
                    echo "                    ";
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["column"], "default", [], "any", false, false, false, 52), "html", null, true);
                    echo "
                  ";
                }
                // line 54
                echo "                </td>
                ";
                // line 55
                if (twig_get_attribute($this->env, $this->source, $context["table"], "has_relation", [], "any", false, false, false, 55)) {
                    // line 56
                    echo "                  <td>";
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["column"], "relation", [], "any", false, false, false, 56), "html", null, true);
                    echo "</td>
                ";
                }
                // line 58
                echo "                <td>";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["column"], "comment", [], "any", false, false, false, 58), "html", null, true);
                echo "</td>
                ";
                // line 59
                if (twig_get_attribute($this->env, $this->source, $context["table"], "has_mime", [], "any", false, false, false, 59)) {
                    // line 60
                    echo "                  <td>";
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["column"], "mime", [], "any", false, false, false, 60), "html", null, true);
                    echo "</td>
                ";
                }
                // line 62
                echo "              </tr>
            ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['column'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 64
            echo "          </tbody>
        </table>

        ";
            // line 67
            if ( !twig_test_empty(twig_get_attribute($this->env, $this->source, $context["table"], "indexes", [], "any", false, false, false, 67))) {
                // line 68
                echo "          <h3>";
echo _gettext("Indexes");
                echo "</h3>

          <table class=\"table table-striped align-middle\">
            <thead>
              <tr>
                <th>";
echo _gettext("Keyname");
                // line 73
                echo "</th>
                <th>";
echo _gettext("Type");
                // line 74
                echo "</th>
                <th>";
echo _gettext("Unique");
                // line 75
                echo "</th>
                <th>";
echo _gettext("Packed");
                // line 76
                echo "</th>
                <th>";
echo _gettext("Column");
                // line 77
                echo "</th>
                <th>";
echo _gettext("Cardinality");
                // line 78
                echo "</th>
                <th>";
echo _gettext("Collation");
                // line 79
                echo "</th>
                <th>";
echo _gettext("Null");
                // line 80
                echo "</th>
                <th>";
echo _gettext("Comment");
                // line 81
                echo "</th>
              </tr>
            </thead>

            <tbody>
              ";
                // line 86
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, $context["table"], "indexes", [], "any", false, false, false, 86));
                foreach ($context['_seq'] as $context["_key"] => $context["index"]) {
                    // line 87
                    echo "                ";
                    $context["columns_count"] = twig_get_attribute($this->env, $this->source, $context["index"], "getColumnCount", [], "method", false, false, false, 87);
                    // line 88
                    echo "                <tr>
                <td rowspan=\"";
                    // line 89
                    echo twig_escape_filter($this->env, ($context["columns_count"] ?? null), "html", null, true);
                    echo "\">";
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["index"], "getName", [], "method", false, false, false, 89), "html", null, true);
                    echo "</td>
                <td rowspan=\"";
                    // line 90
                    echo twig_escape_filter($this->env, ($context["columns_count"] ?? null), "html", null, true);
                    echo "\">";
                    echo twig_escape_filter($this->env, ((twig_get_attribute($this->env, $this->source, $context["index"], "getType", [], "method", true, true, false, 90)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, $context["index"], "getType", [], "method", false, false, false, 90), twig_get_attribute($this->env, $this->source, $context["index"], "getChoice", [], "method", false, false, false, 90))) : (twig_get_attribute($this->env, $this->source, $context["index"], "getChoice", [], "method", false, false, false, 90))), "html", null, true);
                    echo "</td>
                <td rowspan=\"";
                    // line 91
                    echo twig_escape_filter($this->env, ($context["columns_count"] ?? null), "html", null, true);
                    echo "\">";
                    echo twig_escape_filter($this->env, ((twig_get_attribute($this->env, $this->source, $context["index"], "isUnique", [], "method", false, false, false, 91)) ? (_gettext("Yes")) : (_gettext("No"))), "html", null, true);
                    echo "</td>
                <td rowspan=\"";
                    // line 92
                    echo twig_escape_filter($this->env, ($context["columns_count"] ?? null), "html", null, true);
                    echo "\">";
                    echo twig_get_attribute($this->env, $this->source, $context["index"], "isPacked", [], "method", false, false, false, 92);
                    echo "</td>

                ";
                    // line 94
                    $context['_parent'] = $context;
                    $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, $context["index"], "getColumns", [], "method", false, false, false, 94));
                    foreach ($context['_seq'] as $context["_key"] => $context["column"]) {
                        // line 95
                        echo "                  ";
                        if ((twig_get_attribute($this->env, $this->source, $context["column"], "getSeqInIndex", [], "method", false, false, false, 95) > 1)) {
                            // line 96
                            echo "                    <tr>
                  ";
                        }
                        // line 98
                        echo "                  <td>
                    ";
                        // line 99
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["column"], "getName", [], "method", false, false, false, 99), "html", null, true);
                        echo "
                    ";
                        // line 100
                        if ( !twig_test_empty(twig_get_attribute($this->env, $this->source, $context["column"], "getSubPart", [], "method", false, false, false, 100))) {
                            // line 101
                            echo "                      (";
                            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["column"], "getSubPart", [], "method", false, false, false, 101), "html", null, true);
                            echo ")
                    ";
                        }
                        // line 103
                        echo "                  </td>
                  <td>";
                        // line 104
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["column"], "getCardinality", [], "method", false, false, false, 104), "html", null, true);
                        echo "</td>
                  <td>";
                        // line 105
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["column"], "getCollation", [], "method", false, false, false, 105), "html", null, true);
                        echo "</td>
                  <td>";
                        // line 106
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["column"], "getNull", [0 => true], "method", false, false, false, 106), "html", null, true);
                        echo "</td>

                  ";
                        // line 108
                        if ((twig_get_attribute($this->env, $this->source, $context["column"], "getSeqInIndex", [], "method", false, false, false, 108) == 1)) {
                            // line 109
                            echo "                    <td rowspan=\"";
                            echo twig_escape_filter($this->env, ($context["columns_count"] ?? null), "html", null, true);
                            echo "\">";
                            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["index"], "getComments", [], "method", false, false, false, 109), "html", null, true);
                            echo "</td>
                  ";
                        }
                        // line 111
                        echo "                  </tr>
                ";
                    }
                    $_parent = $context['_parent'];
                    unset($context['_seq'], $context['_iterated'], $context['_key'], $context['column'], $context['_parent'], $context['loop']);
                    $context = array_intersect_key($context, $_parent) + $_parent;
                    // line 113
                    echo "              ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['_key'], $context['index'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 114
                echo "            </tbody>
          </table>
        ";
            } else {
                // line 117
                echo "          <p>";
echo _gettext("No index defined!");
                echo "</p>
        ";
            }
            // line 119
            echo "      </div>
    ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['table'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 121
        echo "  </div>

  <p class=\"d-print-none\">
    <button type=\"button\" class=\"btn btn-secondary jsPrintButton\">";
        // line 124
        echo PhpMyAdmin\Html\Generator::getIcon("b_print", _gettext("Print"), true);
        echo "</button>
  </p>
</div>
";
    }

    public function getTemplateName()
    {
        return "database/data_dictionary/index.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  393 => 124,  388 => 121,  381 => 119,  375 => 117,  370 => 114,  364 => 113,  357 => 111,  349 => 109,  347 => 108,  342 => 106,  338 => 105,  334 => 104,  331 => 103,  325 => 101,  323 => 100,  319 => 99,  316 => 98,  312 => 96,  309 => 95,  305 => 94,  298 => 92,  292 => 91,  286 => 90,  280 => 89,  277 => 88,  274 => 87,  270 => 86,  263 => 81,  259 => 80,  255 => 79,  251 => 78,  247 => 77,  243 => 76,  239 => 75,  235 => 74,  231 => 73,  221 => 68,  219 => 67,  214 => 64,  207 => 62,  201 => 60,  199 => 59,  194 => 58,  188 => 56,  186 => 55,  183 => 54,  177 => 52,  173 => 50,  171 => 49,  166 => 47,  161 => 45,  157 => 44,  154 => 43,  148 => 41,  146 => 40,  142 => 39,  138 => 37,  134 => 36,  129 => 33,  123 => 31,  121 => 30,  116 => 29,  110 => 27,  108 => 26,  105 => 25,  101 => 24,  97 => 23,  93 => 22,  86 => 18,  78 => 16,  76 => 15,  72 => 14,  69 => 13,  65 => 12,  58 => 8,  54 => 6,  46 => 4,  44 => 3,  40 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "database/data_dictionary/index.twig", "/srv/http/ez-srs/phpmyadmin/templates/database/data_dictionary/index.twig");
    }
}
