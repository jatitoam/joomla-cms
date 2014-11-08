<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_config
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

/**
 * Text Filters form field.
 *
 * @package     Joomla.Administrator
 * @subpackage  com_config
 * @since       1.6
 */
class JFormFieldFilters extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	public $type = 'Filters';

	/**
	 * @var    int  Current page of permissions (when using pagination)
	 * @since  3.3
	 */
	protected $currentPage = 1;

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
	 * Method to get the field input markup.
	 *
	 * TODO: Add access check.
	 *
	 * @return	string	The field input markup.
	 *
	 * @since	1.6
	 */
	protected function getInput()
	{
		// Pagination parameters
		$jinput = JFactory::getApplication()->input;
		$limit = $jinput->getInt('limit', 20);
		$start = ($this->currentPage != 1) ? ($this->currentPage - 1) * $limit : $jinput->getInt('start', 0);
		$option = $jinput->getString('option', 'com_config');
		$pageNumberLimit = 10;

		// Get the available user groups.
		$groups = $this->getUserGroups(true, $start, $limit);

		// Build the form control.
		$html = array();

		// Open the table.
		$html[] = '<table id="filter-config" class="table table-striped">';

		// The table heading.
		$html[] = '	<thead>';
		$html[] = '	<tr>';
		$html[] = '		<th>';
		$html[] = '			<span class="acl-action">' . JText::_('JGLOBAL_FILTER_GROUPS_LABEL') . '</span>';
		$html[] = '		</th>';
		$html[] = '		<th>';
		$html[] = '			<span class="acl-action" title="' . JText::_('JGLOBAL_FILTER_TYPE_LABEL') . '">' . JText::_('JGLOBAL_FILTER_TYPE_LABEL') . '</span>';
		$html[] = '		</th>';
		$html[] = '		<th>';
		$html[] = '			<span class="acl-action" title="' . JText::_('JGLOBAL_FILTER_TAGS_LABEL') . '">' . JText::_('JGLOBAL_FILTER_TAGS_LABEL') . '</span>';
		$html[] = '		</th>';
		$html[] = '		<th>';
		$html[] = '			<span class="acl-action" title="' . JText::_('JGLOBAL_FILTER_ATTRIBUTES_LABEL') . '">' .
			JText::_('JGLOBAL_FILTER_ATTRIBUTES_LABEL') . '</span>';
		$html[] = '		</th>';
		$html[] = '	</tr>';
		$html[] = '	</thead>';

		// Table footer, index
		$groupCount = $this->getGroupsNumber();
		$pageCount = (empty($limit) ? 1 : ceil((integer) $groupCount / (integer) $limit));

		$html[] = '<tfoot>';
		$html[] = '	<tr>';
		$html[] = '		<td colspan="4">';
		$html[] = RLayoutHelper::render(
			'pagination.ajax.links',
			array(
				'ajaxJS'        => 'getAjaxFiltersPage',
				'numberOfPages' => $pageCount,
				'currentPage'   => $this->currentPage
			)
		);
		$html[] = '		</td>';
		$html[] = '	</tr>';
		$html[] = '	</tfoot>';

		// The table body.
		$html[] = '	<tbody>';

		foreach ($groups as $group)
		{
			if (!isset($this->value[$group->value]))
			{
				$this->value[$group->value] = array('filter_type' => 'BL', 'filter_tags' => '', 'filter_attributes' => '');
			}

			$group_filter = $this->value[$group->value];

			$group_filter['filter_tags']       = !empty($group_filter['filter_tags']) ? $group_filter['filter_tags'] : '';
			$group_filter['filter_attributes'] = !empty($group_filter['filter_attributes']) ? $group_filter['filter_attributes'] : '';

			$html[] = '	<tr>';
			$html[] = '		<th class="acl-groups left">';
			$html[] = '			' . str_repeat('<span class="gi">|&mdash;</span>', $group->level) . $group->text;
			$html[] = '		</th>';
			$html[] = '		<td>';
			$html[] = '				<select name="' . $this->name . '[' . $group->value . '][filter_type]" id="' . $this->id . $group->value . '_filter_type">';
			$html[] = '					<option value="BL"' . ($group_filter['filter_type'] == 'BL' ? ' selected="selected"' : '') . '>' .
				JText::_('COM_CONFIG_FIELD_FILTERS_DEFAULT_BLACK_LIST') . '</option>';
			$html[] = '					<option value="CBL"' . ($group_filter['filter_type'] == 'CBL' ? ' selected="selected"' : '') . '>' .
				JText::_('COM_CONFIG_FIELD_FILTERS_CUSTOM_BLACK_LIST') . '</option>';
			$html[] = '					<option value="WL"' . ($group_filter['filter_type'] == 'WL' ? ' selected="selected"' : '') . '>' .
				JText::_('COM_CONFIG_FIELD_FILTERS_WHITE_LIST') . '</option>';
			$html[] = '					<option value="NH"' . ($group_filter['filter_type'] == 'NH' ? ' selected="selected"' : '') . '>' .
				JText::_('COM_CONFIG_FIELD_FILTERS_NO_HTML') . '</option>';
			$html[] = '					<option value="NONE"' . ($group_filter['filter_type'] == 'NONE' ? ' selected="selected"' : '') . '>' .
				JText::_('COM_CONFIG_FIELD_FILTERS_NO_FILTER') . '</option>';
			$html[] = '				</select>';
			$html[] = '		</td>';
			$html[] = '		<td>';
			$html[] = '				<input name="' . $this->name . '[' . $group->value . '][filter_tags]" id="' . $this->id . $group->value . '_filter_tags" title="' .
				JText::_('JGLOBAL_FILTER_TAGS_LABEL') . '" value="' . $group_filter['filter_tags'] . '"/>';
			$html[] = '		</td>';
			$html[] = '		<td>';
			$html[] = '				<input name="' . $this->name . '[' . $group->value . '][filter_attributes]" id="' .
				$this->id . $group->value . '_filter_attributes" title="' .
				JText::_('JGLOBAL_FILTER_ATTRIBUTES_LABEL') . '" value="' . $group_filter['filter_attributes'] . '"/>';
			$html[] = '		</td>';
			$html[] = '	</tr>';
		}

		$html[] = '	</tbody>';

		// Close the table.
		$html[] = '</table>';

		// Add notes
		$html[] = '<div class="alert">';
		$html[] = '<p>' . JText::_('JGLOBAL_FILTER_TYPE_DESC') . '</p>';
		$html[] = '<p>' . JText::_('JGLOBAL_FILTER_TAGS_DESC') . '</p>';
		$html[] = '<p>' . JText::_('JGLOBAL_FILTER_ATTRIBUTES_DESC') . '</p>';
		$html[] = '</div>';

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
	 * @since	1.6
	 */
	protected function getUserGroups($paginate = false, $start = 0, $limit = 0)
	{
		// Get a database object.
		$db = JFactory::getDBO();

		// Get the user groups from the database.
		$query = $db->getQuery(true);
		$query->select('a.id AS value, a.title AS text, COUNT(DISTINCT b.id) AS level');
		$query->from('#__usergroups AS a');
		$query->join('LEFT', '#__usergroups AS b on a.lft > b.lft AND a.rgt < b.rgt');
		$query->group('a.id, a.title, a.lft');
		$query->order('a.lft ASC');

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
	 * Get the total number of user groups
	 *
	 * @return  int
	 *
	 * @since	3.3
	 */
	protected function getGroupsNumber()
	{
		// Get a database object.
		$db = JFactory::getDBO();

		$query = $db->getQuery(true);
		$query->select('COUNT(a.id) as count');
		$query->from('#__usergroups AS a');
		$db->setQuery($query);
		$count = $db->loadResult();

		return $count;
	}
}
