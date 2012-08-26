<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * TYPOlight webCMS
 * Copyright (C) 2005 Leo Feyer
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at http://www.gnu.org/licenses/.
 *
 * PHP version 5
 * @copyright  Helmut SchottmŸller 2008
 * @author     Helmut SchottmŸller <typolight@aurealis.de>
 * @package    Backend
 * @license    LGPL
 * @filesource
 */


/**
 * Class QueryWidget
 *
 * Provide methods to handle querywidget fields.
 * @copyright  Winans Creative 2012
 * @author     Blair Winans <blair@winanscreative.com>
 * @package    Backend
 */
class QueryWidget extends Widget
{	
	/**
	 * Submit user input
	 * @var boolean
	 */
	protected $blnSubmitInput = true;

	/**
	 * Database table
	 * @var string
	 */
	protected $strTable = '';

	/**
	 * Database field
	 * @var string
	 */
	protected $strField = '';

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'be_widget';
	
	/**
	 * QueryTemplate
	 * @var string
	 */
	protected $strQueryTemplate = 'be_querywidget';
	
	/**
	 * Array of error messages
	 * @var array
	 */
	protected $arrQueryErrors = array();
	
	/**
	 * Column values/label/type
	 * @var array
	 */
	protected $arrColumns = array(array('value'=>'', 'label'=>'-', 'type'=>'varchar'));
	
	/**
	 * Preoperators options
	 * @var array
	 */
	protected $arrPreoperators = array(array('label'=>'Is', 'value'=>'is'), array('label'=>'Is Not', 'value'=>'isnot'));
		
	/**
	 * And/Or options
	 * @var array
	 */
	protected $arrAndOrs = array(array('label'=>'AND', 'value'=>'AND'), array('label'=>'OR', 'value'=>'OR'));
	
	/**
	 * Operator options
	 * @var array
	 */
	protected $arrOperators = array
	(
		array('label'=>'Equal to', 'value'=>'='),
		array('label'=>'Greater than', 'value'=>'>'),
		array('label'=>'Greater than or equal to', 'value'=>'>='),
		array('label'=>'Less than', 'value'=>'<'),
		array('label'=>'Less than or equal to', 'value'=>'<='),
		array('label'=>'Like', 'value'=>'LIKE'),
		array('label'=>'Starting with', 'value'=>'LIKE_START'),
		array('label'=>'Ending with', 'value'=>'LIKE_END'),
	);


	/**
	 * Add specific attributes
	 * @param string
	 * @param mixed
	 */
	public function __set($strKey, $varValue)
	{
		switch ($strKey)
		{
			case 'columns':
				if (is_array($varValue))
				{
					$this->arrColumns = $varValue;
				}
				else
				{
					throw Exception('Error. Please set your columns in array format.');
				}
				break;
				
			case 'operators':
				if (is_array($varValue))
				{
					$this->arrOperators = $varValue;
				}
				else
				{
					throw Exception('Error. Please set your operators in array format.');
				}
				break;
				
			case 'preoperators':
				if (is_array($varValue))
				{
					$this->arrPreoperators = $varValue;
				}
				else
				{
					throw Exception('Error. Please set your preoperators in array format.');
				}
				break;
				
			case 'andors':
				if (is_array($varValue))
				{
					$this->arrAndOrs = $varValue;
				}
				else
				{
					throw Exception('Error. Please set your AND/OR options in array format.');
				}
				break;

			case 'value':
				$this->varValue = deserialize($varValue);
				break;

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}


	/**
	 * Return a parameter
	 * @param string
	 * @return string
	 */
	public function __get($strKey)
	{
		switch ($strKey)
		{
			case 'table':
				return $this->strTable;
				break;
			
			case 'field':
				return $this->strField;
				break;

			default:
				return parent::__get($strKey);
				break;
		}
	}

	/**
	 * Validate input and set value
	 */
	public function validate()
	{
		$tmpRgxp = $this->arrConfiguration['rgxp'];
		$tmpMandatory = $this->arrConfiguration['mandatory'];
		$tmpLabel = $this->strLabel;
		$arrInput = deserialize($this->getPost($this->strName));
		if (!is_array($arrInput)) $arrInput = array();

		foreach ($arrInput as $row => $rowdata)
		{
			foreach ($rowdata as $col => $value)
			{
				if ($this->arrColumns[$col]['unique'] && count(array_unique($allvalues[$col])) != count($allvalues[$col]))
				{
					$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['notunique'], $this->strLabel));
				}
			}
		}

		if (count($this->arrQueryErrors))
		{
			$this->class = 'error';
			$this->arrErrors[] = '';
		}

		$this->strLabel = $tmpLabel;
		$this->varValue = $arrInput;
	}

	/**
	 * Generate the widget and return it as string
	 * @return string
	 */
	public function generate()
	{
		$GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/query_widget/html/querywidget.js';
		$GLOBALS['TL_CSS'][] = 'system/modules/query_widget/html/querywidget.css';

		$arrButtons = array('rnew','rcopy', 'rup', 'rdown', 'rdelete', 'rindent');

		$strCommand = 'cmd_' . $this->strField;
		$emptyarray = array();
		for ($i = 0; $i < count($this->arrColumns); $i++) array_push($emptyarray, '');
		
		// Change the order
		if ($this->Input->get($strCommand) && is_numeric($this->Input->get('rid')) && is_numeric($this->currentRecord) && $this->Input->get('id') == $this->currentRecord && strlen($this->strTable) && strlen($this->strField))
		{
			$this->import('Database');

			switch ($this->Input->get($strCommand))
			{
				case 'rnew':
					array_insert($this->varValue, $this->Input->get('rid') + 1, array($emptyarray));
					break;

				case 'rcopy':
					$this->varValue = array_duplicate($this->varValue, $this->Input->get('rid'));
					break;

				case 'rup':
					$this->varValue = array_move_up($this->varValue, $this->Input->get('rid'));
					break;

				case 'rdown':
					$this->varValue = array_move_down($this->varValue, $this->Input->get('rid'));
					break;

				case 'rdelete':
					$this->varValue = array_delete($this->varValue, $this->Input->get('rid'));
					break;

				case 'rindent':
					array_insert($this->varValue, $this->Input->get('rid') + 1, array($emptyarray));
					array_insert($this->varValue, $this->Input->get('rid') - 1, array($emptyarray));
					break;
			}

			$this->Database->prepare("UPDATE " . $this->strTable . " SET " . $this->strField . "=? WHERE id=?")
						   ->execute(serialize($this->varValue), $this->currentRecord);

			$this->redirect(preg_replace('/&(amp;)?rid=[^&]*/i', '', preg_replace('/&(amp;)?' . preg_quote($strCommand, '/') . '=[^&]*/i', '', $this->Environment->request)));
		}

		// Make sure there is at least an empty array
		if (!is_array($this->varValue) || !$this->varValue[0])
		{
			$this->varValue = array($emptyarray);
		}
		
		// Set template variables
		$objTemplate = new BackendTemplate($this->strQueryTemplate);
		$objTemplate->strId = $this->strId;
		$objTemplate->attributes = $this->getAttributes();
		$objTemplate->arrColumns = $this->arrColumns;
		$objTemplate->arrOperators = $this->arrOperators;
		$objTemplate->arrPreoperators = $this->arrPreoperators;
		$objTemplate->arrAndOrs = $this->arrAndOrs;
		$objTemplate->varValue = $this->varValue;
		$objTemplate->arrQueryErrors = $this->arrQueryErrors;
		$objTemplate->arrNonGroupBtns = array('rnew', 'rcopy', 'rindent');	// Don't show "new", "copy", or "indent" on group rows
		$objTemplate->levelLabel = $GLOBALS['TL_LANG']['query_widget']['level'];
		
		$buttons = array();
		$hasTitles = array_key_exists('buttonTitles', $this->arrConfiguration) && is_array($this->arrConfiguration['buttonTitles']);
		
		// Build buttons
		foreach ($arrButtons as $button)
		{
			$buttontitle = ($hasTitles && array_key_exists($button, $this->arrConfiguration['buttonTitles'])) ? $this->arrConfiguration['buttonTitles'][$button] : $GLOBALS['TL_LANG'][$this->strTable][$button][0];
			if ($button == 'rindent')
				$img = '<img src="system/modules/query_widget/html/indentRow.png" title="Indent this row to create a grouping section" />';
			else
				$img = $this->generateImage(substr($button, 1).'.gif', $GLOBALS['TL_LANG'][$this->strTable][$button][0], 'class="tl_querywidget_img"');
			
			array_push($buttons,
				array(
					'key'		=> $button,
					'href'		=> $this->addToUrl('&amp;'.$strCommand.'='.$button.'&amp;rid=%s&amp;id='.$this->currentRecord),
					'class'		=> $button,
					'title'		=> specialchars($buttontitle),
					'onclick'	=> 'QueryWidget'.$this->strId.'.doCommand(this, \''.$button.'\', %s); return false;',
					'img'		=> $img,
				)
			);
		}
		
		$objTemplate->arrButtons = $buttons;
		
		// Add script
		$GLOBALS['TL_MOOTOOLS'][] = '
<script>
window.addEvent(\'domready\', function(){
		QueryWidget'.$this->strId.' = new QueryWidget({container:\'ctrl_'.$this->strId.'\'});
});
</script>';

$this->getSQL();
		
		return $objTemplate->parse();
	}
	
	
	
	public function getSQL()
	{
		// If no value for some reason, return " 1" so the query will not fail after the "WHERE"
		if (!is_array($this->varValue) || count($this->varValue) == 0)
			return ' 1';
		
		$strSQL = ' ';
		$arrValues = array();
		//$intLevel = 1;
		
		foreach ($this->varValue as $intIndex=>$arrQueryRow)
		{
			$strPreOperator = '';
			$blnGroupStart = ($arrQueryRow['value'] == '|group_start|') ? true : false;
			$blnGroupStop = ($arrQueryRow['value'] == '|group_stop|') ? true : false;
			$blnGroupRow = ($blnGroupStart || $blnGroupStop) ? true : false;
			$blnHideAndOr = ($intIndex == 0 || $blnGroupStop || $this->varValue[$intIndex - 1]['value'] == '|group_start|') ? true : false;
			//$intLevel = ($blnGroupStart) ? $intLevel+1 : ($blnGroupStop ? $intLevel-1 : $intLevel);
			
			if ($blnGroupStop)
			{
				$strSQL .= " )";
				continue;
			}
			
			foreach ($arrQueryRow as $key=>$val)
			{
				switch ($key)
				{
					case 'andor':
					
						if (!$blnHideAndOr)
						{
							$strSQL .= " " . $val;
						}
						if ($blnGroupStart)
						{
							$strSQL .= " (";
							continue 3;
						}
						break;
						
					case 'column':
					
						$strSQL .= " " . $val;
						break;
						
					case 'preoperator':
					
						$strPreOperator = $val;
						break;
					
					case 'operator':
		
						switch ($strPreOperator)
						{
							case 'is':
						
								switch ($val)
								{
									case '=':
									case '>':
									case '>=':
									case '<':
									case '<=':
										
										$strSQL .= " " . $val . " ?";
										break;
										
									case 'LIKE':
										
										$strSQL .= " LIKE CONCAT('%', ?, '%')";
										break;
									
									case 'LIKE_START':
										
										$strSQL .= " LIKE CONCAT(?, '%')";
										break;
										
									case 'LIKE_END':
										
										$strSQL .= " LIKE CONCAT(?, '%')";
										break;
										
									default:
										
										// Hook: Allow custom "is" operators 
										if (isset($GLOBALS['TL_HOOKS']['query_widget']['custom_is_operators']) && is_array($GLOBALS['TL_HOOKS']['query_widget']['custom_is_operators']))
										{
											foreach ($GLOBALS['TL_HOOKS']['query_widget']['custom_is_operators'] as $callback)
											{
												$this->import($callback[0]);
												$strSQL .= $this->{$callback[0]}->{$callback[1]}($arrQueryRow, $intIndex, $val, $this);
											}
										}
										
										break;
								}
								
								break;
								
							case 'isnot':
						
								switch ($val)
								{
									case '=':
										
										$strSQL .= " <> ?";
										break;
									
									case '>':
										
										$strSQL .= " <= ?";
										break;
									
									case '>=':
										
										$strSQL .= " < ?";
										break;
									
									case '<':
										
										$strSQL .= " >= ?";
										break;
									
									case '<=':
										
										$strSQL .= " > ?";
										break;
									
									case 'LIKE':
										
										$strSQL .= " LIKE CONCAT('%', ?, '%')";
										break;
									
									case 'LIKE_START':
										
										$strSQL .= " LIKE CONCAT(?, '%')";
										break;
										
									case 'LIKE_END':
										
										$strSQL .= " LIKE CONCAT(?, '%')";
										break;
										
									default:
										
										// Hook: Allow custom "is not" operators 
										if (isset($GLOBALS['TL_HOOKS']['query_widget']['custom_isnot_operators']) && is_array($GLOBALS['TL_HOOKS']['query_widget']['custom_isnot_operators']))
										{
											foreach ($GLOBALS['TL_HOOKS']['query_widget']['custom_isnot_operators'] as $callback)
											{
												$this->import($callback[0]);
												$strSQL .= $this->{$callback[0]}->{$callback[1]}($arrQueryRow, $intIndex, $val, $this);
											}
										}
										
										break;
								}
								
								break;
								
							default:
								
								// Hook: Allow custom preoperators 
								if (isset($GLOBALS['TL_HOOKS']['query_widget']['custom_preoperators']) && is_array($GLOBALS['TL_HOOKS']['query_widget']['custom_preoperators']))
								{
									foreach ($GLOBALS['TL_HOOKS']['query_widget']['custom_preoperators'] as $callback)
									{
										$this->import($callback[0]);
										$strSQL .= $this->{$callback[0]}->{$callback[1]}($arrQueryRow, $intIndex, $val, $this);
									}
								}
								
								break;
						}
						
						break;
						
					case 'value':
					
						$arrValues[] = $val;
						break;
				}

			}
		}
		
		return array($strSQL, $arrValues);
	}
	
}


?>