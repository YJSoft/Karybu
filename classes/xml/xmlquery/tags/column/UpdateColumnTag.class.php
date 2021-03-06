<?php
/**
 * Models the &lt;column&gt; tag inside an XML Query file whose action is 'update'
 *
 * @author Corina Udrescu (corina.udrescu@arnia.ro)
 * @package classes\xml\xmlquery\tags\column
 * @version 0.1
 */
class UpdateColumnTag extends ColumnTag
{
    /**
     * Argument
     *
     * @var QueryArgument object
     */
    var $argument;

    /**
     * Default value
     *
     * @var string
     */
    var $default_value;

    /**
     * Constructor
     *
     * @param object $column
     * @return void
     */
    function UpdateColumnTag($column)
    {
        parent::ColumnTag($column->attrs->name);

        $oDB = DB::getInstance();
        $dbParser = $oDB->getParser();
        $this->name = $dbParser->parseColumnName($this->name);

        if ($column->attrs->var) {
            $this->argument = new QueryArgument($column);
        } else {
            if (strpos($column->attrs->default, '.') !== false) {
                $this->default_value = "'" . $dbParser->parseColumnName($column->attrs->default) . "'";
            } else {
                $default_value = new DefaultValue($this->name, $column->attrs->default);
                if ($default_value->isOperation()) {
                    $this->argument = new QueryArgument($column, true);
                } //else $this->default_value = $dbParser->parseColumnName($column->attrs->default);
                else {
                    $this->default_value = $default_value->toString();
                    if ($default_value->isStringFromFunction()) {
                        $this->default_value = '"\'".' . $this->default_value . '."\'"';
                    }
                    if ($default_value->isString()) {
                        $this->default_value = '"' . $this->default_value . '"';
                    }
                }


            }
        }
    }

    /**
     * Returns the string to be output in the cache file
     *
     * @return string
     */
    function getExpressionString()
    {
        if ($this->argument) {
            return sprintf(
                'new UpdateExpression(\'%s\', ${\'%s_argument\'})'
                ,
                $this->name
                ,
                $this->argument->argument_name
            );
        } else {
            return sprintf(
                'new UpdateExpressionWithoutArgument(\'%s\', %s)'
                ,
                $this->name
                ,
                $this->default_value
            );
        }
    }

    /**
     * Returns the Argument associated with this update statement
     *
     * @return QueryArgument
     */
    function getArgument()
    {
        return $this->argument;
    }
}

?>
