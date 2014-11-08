<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * Form Field class for the Joomla Platform.
 * Field for assigning permissions to groups for a given asset
 *
 * @package     Joomla.Platform
 * @subpackage  Form
 * @see         JAccess
 * @since       11.1
 */
class JFormFieldRules extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	protected $type = 'Rules';

	/**
	 * The section.
	 *
	 * @var    string
	 * @since  3.2
	 */
	protected $section;

	/**
	 * The component.
	 *
	 * @var    string
	 * @since  3.2
	 */
	protected $component;

	/**
	 * The assetField.
	 *
	 * @var    string
	 * @since  3.2
	 */
	protected $assetField;

	/**
	 * @var    int  Current page of permissions (when using pagination)
	 * @since  3.3
	 */
	protected $currentPage = 1;

	/**
	 * Constructor that allows to copy field parameter
	 *
	 * @param   object  $previousObject  Object from parent class
	 *
	 * @since  3.3
	 *
	 */
	public function __construct($previousObject = null)
	{
		if (empty($previousObject))
		{
			parent::__construct();

			return $this;
		}

		foreach (get_object_vars($previousObject) as $key => $value)
		{
			$this->$key = $value;
		}

		return $this;
	}

	/**
	 * Method to get certain otherwise inaccessible properties from the form field object.
	 *
	 * @param   string  $name  The property name for which to the the value.
	 *
	 * @return  mixed  The property value or null.
	 *
	 * @since   3.2
	 */
	public function __get($name)
	{
		switch ($name)
		{
			case 'section':
			case 'component':
			case 'assetField':
				return $this->$name;
		}

		return parent::__get($name);
	}

	/**
	 * Method to set certain otherwise inaccessible properties of the form field object.
	 *
	 * @param   string  $name   The property name for which to the the value.
	 * @param   mixed   $value  The value of the property.
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	public function __set($name, $value)
	{
		switch ($name)
		{
			case 'section':
			case 'component':
			case 'assetField':
				$this->$name = (string) $value;
				break;

			default:
				parent::__set($name, $value);
		}
	}

	/**
	 * Method to attach a JForm object to the field.
	 *
	 * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the <field /> tag for the form field object.
	 * @param   mixed             $value    The form field value to validate.
	 * @param   string            $group    The field name group control value. This acts as as an array container for the field.
	 *                                      For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                      full field name would end up being "bar[foo]".
	 *
	 * @return  boolean  True on success.
	 *
	 * @see     JFormField::setup()
	 * @since   3.2
	 */
	public function setup(SimpleXMLElement $element, $value, $group = null)
	{
		$return = parent::setup($element, $value, $group);

		if ($return)
		{
			$this->section    = $this->element['section'] ? (string) $this->element['section'] : '';
			$this->component  = $this->element['component'] ? (string) $this->element['component'] : '';
			$this->assetField = $this->element['asset_field'] ? (string) $this->element['asset_field'] : 'asset_id';
		}

		return $return;
	}

	/**
	 * Current page setter function.
	 *
	 * @param   int  $page  Page to set current page.
	 *
	 * @return null
	 *
	 * @since   3.3
	 */
	public function setCurrentPage($page)
	{
		$this->currentPage = $page;
	}

	/**
	 * Setter value for loading javascript function for page change.
	 *
	 * @param   bool  $loadJs  Boolean value
	 *
	 * @return void
	 */
	public function loadJs($loadJs = true)
	{
		if ($loadJs)
		{
			$this->element['load_js'] = 'true';
		}
		else
		{
			$this->element['load_js'] = 'false';
		}
	}

	/**
	 * Method to get the field input markup for Access Control Lists.
	 * Optionally can be associated with a specific component and section.
	 * This method gets called when accessing $field->input;
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   11.1
	 * @todo:   Add access check.
	 */
	public function getInput()
	{
		// Prepare output
		$html = array();

		JHtml::_('bootstrap.tooltip');

		// Pagination parameters
		$jinput = JFactory::getApplication()->input;
		$limit = $jinput->getInt('limit', 20);
		$start = ($this->currentPage != 1) ? ($this->currentPage - 1) * $limit : $jinput->getInt('start', 0);
		$groupCount = $this->getGroupsNumber();
		$pageCount = (empty($limit) ? 1 : ceil((integer) $groupCount / (integer) $limit));
		$pagination = new JPagination($groupCount, $start, $limit);

		// Initialise some field attributes.
		$section = $this->section;
		$component = $this->component;
		$assetField = $this->assetField;

		// Get the actions for the asset.
		$actions = JAccess::getActions($component, $section);

		// Iterate over the children and add to the actions.
		foreach ($this->element->children() as $el)
		{
			if ($el->getName() == 'action')
			{
				$actions[] = (object) array('name' => (string) $el['name'], 'title' => (string) $el['title'],
					'description' => (string) $el['description']);
			}
		}

		// Get the explicit rules for this asset.
		if ($section == 'component')
		{
			// Need to find the asset id by the name of the component.
			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
				->select($db->quoteName('id'))
				->from($db->quoteName('#__assets'))
				->where($db->quoteName('name') . ' = ' . $db->quote($component));
			$db->setQuery($query);
			$assetId = (int) $db->loadResult();
		}
		else
		{
			// Find the asset id of the content.
			// Note that for global configuration, com_config injects asset_id = 1 into the form.
			$assetId = $this->form->getValue($assetField);
		}

		// Get the rules for just this asset (non-recursive).
		$assetRules = JAccess::getAssetRules($assetId);

		// Get the available user groups.
		$groups = $this->getUserGroups(true, $start, $limit);

		if ($this->element['full_catalog'] == 'true')
		{
			$html[] = '<div id="permissions-catalog">';
		}

		// Description
		$html[] = '<p class="rule-desc">' . JText::_('JLIB_RULES_SETTINGS_DESC') . '</p>';

		// Begin tabs
		$html[] = '<div id="permissions-sliders" class="tabbable tabs-left">';

		// Building tab nav
		$html[] = '<ul class="nav nav-tabs">';

		foreach ($groups as $i => $group)
		{
			// Initial Active Tab
			$active = "";

			if ($i == 0)
			{
				$active = "active";
			}

			$html[] = '<li class="' . $active . '">';
			$html[] = '<a href="#permission-' . $group->value . '" data-toggle="tab">';
			$html[] = str_repeat('<span class="level">&ndash;</span> ', $curLevel = $group->level) . $group->text;
			$html[] = '</a>';
			$html[] = '</li>';
		}

		$html[] = '</ul>';

		$html[] = '<div class="tab-content">';

		// Start a row for each user group.
		foreach ($groups as $i => $group)
		{
			// Initial Active Pane
			$active = "";

			if ($i == 0)
			{
				$active = " active";
			}

			$html[] = '<div class="tab-pane' . $active . '" id="permission-' . $group->value . '">';
			$html[] = '<table class="table table-striped">';
			$html[] = '<thead>';
			$html[] = '<tr>';

			$html[] = '<th class="actions" id="actions-th' . $group->value . '">';
			$html[] = '<span class="acl-action">' . JText::_('JLIB_RULES_ACTION') . '</span>';
			$html[] = '</th>';

			$html[] = '<th class="settings" id="settings-th' . $group->value . '">';
			$html[] = '<span class="acl-action">' . JText::_('JLIB_RULES_SELECT_SETTING') . '</span>';
			$html[] = '</th>';

			// The calculated setting is not shown for the root group of global configuration.
			$canCalculateSettings = ($group->parent_id || !empty($component));

			if ($canCalculateSettings)
			{
				$html[] = '<th id="aclactionth' . $group->value . '">';
				$html[] = '<span class="acl-action">' . JText::_('JLIB_RULES_CALCULATED_SETTING') . '</span>';
				$html[] = '</th>';
			}

			$html[] = '</tr>';
			$html[] = '</thead>';

			$html[] = '<tbody>';

			foreach ($actions as $action)
			{
				$html[] = '<tr>';
				$html[] = '<td headers="actions-th' . $group->value . '">';
				$html[] = '<label for="' . $this->id . '_' . $action->name . '_' . $group->value . '" class="hasTooltip" title="'
					. htmlspecialchars(JText::_($action->title) . ' ' . JText::_($action->description), ENT_COMPAT, 'UTF-8') . '">';
				$html[] = JText::_($action->title);
				$html[] = '</label>';
				$html[] = '</td>';

				$html[] = '<td headers="settings-th' . $group->value . '">';

				$html[] = '<select class="' . $this->id . '_visible input-small " name="' . $this->name . '[' . $action->name . '][' . $group->value . ']"' .
					' onchange="onValueChange(jQuery(this))" id="' . $this->id . '_' . str_replace('.', '_', $action->name) . '_' . $group->value . '"' .
					' title="' . JText::sprintf('JLIB_RULES_SELECT_ALLOW_DENY_GROUP', JText::_($action->title), trim($group->text)) . '">';

				$inheritedRule = JAccess::checkGroup($group->value, $action->name, $assetId);

				// Get the actual setting for the action for this group.
				$assetRule = $assetRules->allow($action->name, $group->value);

				// Build the dropdowns for the permissions sliders

				// The parent group has "Not Set", all children can rightly "Inherit" from that.
				$html[] = '<option value=""' . ($assetRule === null ? ' selected="selected"' : '') . '>'
					. JText::_(empty($group->parent_id) && empty($component) ? 'JLIB_RULES_NOT_SET' : 'JLIB_RULES_INHERITED') . '</option>';
				$html[] = '<option value="1"' . ($assetRule === true ? ' selected="selected"' : '') . '>' . JText::_('JLIB_RULES_ALLOWED')
					. '</option>';
				$html[] = '<option value="0"' . ($assetRule === false ? ' selected="selected"' : '') . '>' . JText::_('JLIB_RULES_DENIED')
					. '</option>';

				$html[] = '</select>&#160; ';

				// If this asset's rule is allowed, but the inherited rule is deny, we have a conflict.
				if (($assetRule === true) && ($inheritedRule === false))
				{
					$html[] = JText::_('JLIB_RULES_CONFLICT');
				}

				$html[] = '</td>';

				// Build the Calculated Settings column.
				// The inherited settings column is not displayed for the root group in global configuration.
				if ($canCalculateSettings)
				{
					$html[] = '<td headers="aclactionth' . $group->value . '">';

					// This is where we show the current effective settings considering currrent group, path and cascade.
					// Check whether this is a component or global. Change the text slightly.

					if (JAccess::checkGroup($group->value, 'core.admin', $assetId) !== true)
					{
						if ($inheritedRule === null)
						{
							$html[] = '<span class="label label-important">' . JText::_('JLIB_RULES_NOT_ALLOWED') . '</span>';
						}
						elseif ($inheritedRule === true)
						{
							$html[] = '<span class="label label-success">' . JText::_('JLIB_RULES_ALLOWED') . '</span>';
						}
						elseif ($inheritedRule === false)
						{
							if ($assetRule === false)
							{
								$html[] = '<span class="label label-important">' . JText::_('JLIB_RULES_NOT_ALLOWED') . '</span>';
							}
							else
							{
								$html[] = '<span class="label"><i class="icon-lock icon-white"></i> ' . JText::_('JLIB_RULES_NOT_ALLOWED_LOCKED')
									. '</span>';
							}
						}
					}
					elseif (!empty($component))
					{
						$html[] = '<span class="label label-success"><i class="icon-lock icon-white"></i> ' . JText::_('JLIB_RULES_ALLOWED_ADMIN')
							. '</span>';
					}
					else
					{
						// Special handling for  groups that have global admin because they can't  be denied.
						// The admin rights can be changed.
						if ($action->name === 'core.admin')
						{
							$html[] = '<span class="label label-success">' . JText::_('JLIB_RULES_ALLOWED') . '</span>';
						}
						elseif ($inheritedRule === false)
						{
							// Other actions cannot be changed.
							$html[] = '<span class="label label-important"><i class="icon-lock icon-white"></i> '
								. JText::_('JLIB_RULES_NOT_ALLOWED_ADMIN_CONFLICT') . '</span>';
						}
						else
						{
							$html[] = '<span class="label label-success"><i class="icon-lock icon-white"></i> ' . JText::_('JLIB_RULES_ALLOWED_ADMIN')
								. '</span>';
						}
					}

					$html[] = '</td>';
				}

				$html[] = '</tr>';
			}

			$html[] = '</tbody>';
			$html[] = '</table></div>';
		}

		$html[] = '</div></div>';

		$html[] = '	<table style="width: 100%"><tfoot>';
		$html[] = '	<tr>';
		$html[] = '		<td colspan="4">';
		$html[] = $pagination->getListFooter();
		$html[] = '		</td>';
		$html[] = '	</tr>';
		$html[] = '	</tfoot></table>';

		$html[] = '<div class="alert">';

		if ($section == 'component' || $section == null)
		{
			$html[] = JText::_('JLIB_RULES_SETTING_NOTES');
		}
		else
		{
			$html[] = JText::_('JLIB_RULES_SETTING_NOTES_ITEM');
		}

		$html[] = '</div>';

		if ($this->element['full_catalog'] == 'true')
		{
			$html[] = '</div>';

			if ($this->element['load_js'] == 'true')
			{
				$html[] = ' <div id="inputs"></div>';
				$html[] = '<script type="text/javascript">';
				$html[] = 'function getAjaxPage(page){
						jQuery.ajax({
							url : "index.php?option=com_config&task=config.getpage",
							type : "post",
							data : {"page" : page},
							dataType : "html",
							beforeSend: function(){
								jQuery("#permissions-catalog").html("<div class=\'spinner pagination-centered\'>' .
									str_replace('"', '\'', JHtml::image('media/jui/img/ajax-loader.gif', '')) .
									'</div>");
							}
						}).fail(function(){
							jQuery("#permissions-catalog").html("N/A");
						}).done(function(data){
							jQuery("#permissions-catalog").html(data);
							jQuery("select.' . $this->id . '_visible").each(function(index) {
								var id = jQuery(this).attr("id") + "_change";
								var input = jQuery("input#" + id);

								if (input.length > 0)
								{
									jQuery(this).val(input.val());
								}
							});
							jQuery("select.' . $this->id . '_visible").chosen();
						});
					}';

				$html[] = 'function onValueChange(element) {
				var id = element.attr("id");
				var input = jQuery("input#" + id + "_change");

				if (input.length > 0)
				{
					input.val(element.val());
				}
				else
				{
					var inputs = jQuery("#inputs");
					var parts = id.split("_");
					id = id + "_change";
					var name = parts[0] + "[change]" + "[";

					for(i = 2; i < parts.length; i++)
					{
						if (i == parts.length - 1)
						{
							name = name + "][" + parts[i] + "]";
						}
						else if (i == parts.length - 2)
						{
							name = name + parts[i];
						}
						else
						{
							name = name + parts[i] + ".";
						}
					}

					var value = element.val();
					inputs.html(inputs.html() + "<input type=\"hidden\" name=\"" + name + "\" id=\"" + id + "\" value=\"" + value + "\"/>");
				}
			}';

				$html[] = '</script>';
			}
		}

		return implode("\n", $html);
	}

	/**
	 * Get a list of the user groups
	 *
	 * @param   bool  $paginate  Defines if pagination should be used
	 * @param   int   $start     Start
	 * @param   int   $limit     Limit
	 *
	 * @return  array
	 *
	 * @since   11.1
	 */
	protected function getUserGroups($paginate = false, $start = 0, $limit = 0)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('a.id AS value, a.title AS text, COUNT(DISTINCT b.id) AS level, a.parent_id')
			->from('#__usergroups AS a')
			->join('LEFT', $db->quoteName('#__usergroups') . ' AS b ON a.lft > b.lft AND a.rgt < b.rgt')
			->group('a.id, a.title, a.lft, a.rgt, a.parent_id')
			->order('a.lft ASC');

		if ($paginate)
		{
			$db->setQuery($query, $start, $limit);
		}
		else
		{
			$db->setQuery($query);
		}

		$options = $db->loadObjectList();

		return $options;
	}

	/**
	 * Get groups number function.
	 *
	 * @return  int
	 *
	 * @since   3.3
	 */
	protected function getGroupsNumber()
	{
		// Get a database object.
		$db = JFactory::getDBO();

		$query = $db->getQuery(true);
		$query->select('COUNT(id)');
		$query->from('#__usergroups');
		$db->setQuery($query);
		$count = (int) $db->loadResult();

		return $count;
	}
}
