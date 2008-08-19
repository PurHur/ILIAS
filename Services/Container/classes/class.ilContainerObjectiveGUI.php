<?php
/*
        +-----------------------------------------------------------------------------+
        | ILIAS open source                                                           |
        +-----------------------------------------------------------------------------+
        | Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
        |                                                                             |
        | This program is free software; you can redistribute it and/or               |
        | modify it under the terms of the GNU General Public License                 |
        | as published by the Free Software Foundation; either version 2              |
        | of the License, or (at your option) any later version.                      |
        |                                                                             |
        | This program is distributed in the hope that it will be useful,             |
        | but WITHOUT ANY WARRANTY; without even the implied warranty of              |
        | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
        | GNU General Public License for more details.                                |
        |                                                                             |
        | You should have received a copy of the GNU General Public License           |
        | along with this program; if not, write to the Free Software                 |
        | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
        +-----------------------------------------------------------------------------+
*/


include_once("./Services/Container/classes/class.ilContainerContentGUI.php");

/**
* GUI class for course objective view
*
* @author Stefan Meyer <smeyer.ilias@gmx.de>
* @version $Id$
*
* @ingroup ServicesContainer
*/
class ilContainerObjectiveGUI extends ilContainerContentGUI
{
	const MATERIALS_TESTS = 1;
	const MATERIALS_OTHER = 2;
	
	/**
	 * Constructor
	 *
	 * @access public
	 * @param object container gui object
	 * @return
	 */
	public function __construct($a_container_gui)
	{
		parent::__construct($a_container_gui);
	}
	
	/**
	 * Impementation of abstract method getMainContent
	 *
	 * @access public
	 * @return
	 */
	public function getMainContent()
	{
		global $lng,$ilTabs;

		$ilTabs->setSubTabActive($this->getContainerObject()->getType().'_content');


		include_once './classes/class.ilObjectListGUIFactory.php';

		$tpl = new ilTemplate ("tpl.container_page.html", true, true,"Services/Container");

		// Feedback
		// @todo
//		$this->__showFeedBack();

		$this->items = $this->getContainerObject()->getSubItems($this->getContainerGUI()->isActiveAdministrationPanel());

		$this->showStatus($tpl);
		$this->showObjectives($tpl);
		$this->showMaterials($tpl,self::MATERIALS_TESTS);
		$this->showMaterials($tpl,self::MATERIALS_OTHER);
			
		// @todo: Move this completely to GUI class?
		$this->getContainerGUI()->showAdministrationPanel($tpl);
		$this->getContainerGUI()->showPermanentLink($tpl);

		return $tpl->get();
	}
	
	/**
	 * show status
	 *
	 * @access public
	 * @param
	 * @return
	 */
	public function showStatus($tpl)
	{
		global $ilUser,$lng;
		
		include_once('./Modules/Course/classes/class.ilCourseObjectiveResultCache.php');
		
		$tpl->setCurrentBlock('cont_page_content');
		
		$info_tpl = new ilTemplate('tpl.crs_objectives_view_info_table.html',true,true,'Modules/Course');
		$info_tpl->setVariable("INFO_STRING",$lng->txt('crs_objectives_info_'.
			ilCourseObjectiveResultCache::getStatus($ilUser->getId(),$this->getContainerObject()->getId())));
		
		$tpl->setVariable('CONTAINER_PAGE_CONTENT',$info_tpl->get());
		$tpl->parseCurrentBlock();
		
	}
	
	/**
	 * show objectives
	 *
	 * @access public
	 * @param object $tpl template object
	 * @return
	 */
	public function showObjectives($a_tpl)
	{
		global $lng,$ilSetting;
		
		$this->clearAdminCommandsDetermination();
		$output_html = $this->getContainerGUI()->getContainerPageHTML();
		
		// get embedded blocks
		if ($output_html != "")
		{
			$output_html = $this->insertPageEmbeddedBlocks($output_html);
		}

		$tpl = $this->newBlockTemplate();
		
		// All objectives
		include_once './Modules/Course/classes/class.ilCourseObjective.php';
		if(!count($objective_ids = ilCourseObjective::_getObjectiveIds($this->getContainerObject()->getId())))
		{
			return false;
		}
		
		include_once('./Modules/Course/classes/class.ilCourseObjectiveListGUI.php');
		$this->objective_list_gui = new ilCourseObjectiveListGUI();
		$this->objective_list_gui->setContainerObject($this->getContainerGUI());
		if ($ilSetting->get("icon_position_in_lists") == "item_rows")
		{
			$this->objective_list_gui->enableIcon(true);
		}
		
		
		$item_html = array();
		foreach($objective_ids as $objective_id)
		{
			if($html = $this->renderObjective($objective_id))
			{
				$item_html[] = $html;
			}
		}
		
		// if we have at least one item, output the block
		if (count($item_html) > 0)
		{
			$this->addHeaderRow($tpl,'lobj',$lng->txt('crs_objectives'));
			foreach($item_html as $h)
			{
				$this->addStandardRow($tpl, $h);
			}
		}

		$output_html .= $tpl->get();
		$a_tpl->setCurrentBlock('cont_page_content');
		$a_tpl->setVariable("CONTAINER_PAGE_CONTENT", $output_html);
		$a_tpl->parseCurrentBlock();
		
	
	}
	
	
	
	/**
	 * Show all other (no assigned tests, no assigned materials) materials
	 *
	 * @access public
	 * @param object $tpl template object
	 * @return void
	 */
	public function showMaterials($a_tpl,$a_mode)
	{
		global $ilAccess, $lng;

		$this->clearAdminCommandsDetermination();
		
		$output_html = $this->getContainerGUI()->getContainerPageHTML();
		
		// get embedded blocks
		if ($output_html != "")
		{
			$output_html = $this->insertPageEmbeddedBlocks($output_html);
		}
		
		$tpl = $this->newBlockTemplate();
		if (is_array($this->items["_all"]))
		{
			// all rows
			$item_html = array();
			foreach($this->items["_all"] as $k => $item_data)
			{
				if($a_mode == self::MATERIALS_TESTS and $item_data['type'] != 'tst')
				{
					continue;
				}
				
				if($this->rendered_items[$item_data["child"]] !== true)
				{
					$this->rendered_items[$item_data['child']] = true;
					$html = $this->renderItem($item_data,$a_mode == self::MATERIALS_TESTS ? false : true);
					if ($html != "")
					{
						$item_html[] = $html;
					}
				}
			}
			
			// if we have at least one item, output the block
			if (count($item_html) > 0)
			{
				switch($a_mode)
				{
					case self::MATERIALS_TESTS:
						$txt = $lng->txt('objs_tst');
						break;
						
					case self::MATERIALS_OTHER:
						$txt = $lng->txt('crs_other_resources');
						break;
				}
				
				$this->addHeaderRow($tpl,$a_mode == self::MATERIALS_TESTS ? 'tst' : '',$txt);
				foreach($item_html as $h)
				{
					$this->addStandardRow($tpl, $h);
				}
			}
		}

		$output_html .= $tpl->get();
		$a_tpl->setCurrentBlock('cont_page_content');
		$a_tpl->setVariable("CONTAINER_PAGE_CONTENT", $output_html);
		$a_tpl->parseCurrentBlock();
	}
	
	/**
	 * render objective
	 *
	 * @access protected
	 * @param int objective id
	 * @return string html
	 */
	protected function renderObjective($a_objective_id)
	{
		global $ilUser;
		
		include_once('./Modules/Course/classes/class.ilCourseObjective.php');
		$objective = new ilCourseObjective($this->getContainerObject(),$a_objective_id);
		
		$pos = 1;
		foreach($this->getContainerObject()->items_obj->getItemsByObjective($a_objective_id) as $item) 
		{
			$item_list_gui2 = $this->getItemGUI($item);
			$item_list_gui2->enableIcon(true);
			if ($this->getContainerGUI()->isActiveAdministrationPanel())
			{
				$item_list_gui2->enableCheckbox(true);
				if ($this->getContainerObject()->getOrderType() == IL_CNTR_SORT_MANUAL)
				{
					$item_list_gui2->setPositionInputField("[".$item["ref_id"]."]",
						sprintf('%.1f', $pos));
					$pos++;
				}
				
			}
			$this->rendered_items[$item['child']] = true;
			$sub_item_html = $item_list_gui2->getListItemHTML($item['ref_id'],
				$item['obj_id'], $item['title'], $item['description']);
				
			$this->determineAdminCommands($item["ref_id"],
				$item_list_gui2->adminCommandsIncluded());
			$this->objective_list_gui->addSubItemHTML($sub_item_html);
		}
		
		
		$html = $this->objective_list_gui->getListItemHTML(
			0,
			$a_objective_id,
			$objective->getTitle(),
			$objective->getDescription());
			
		return $html;
	}
	
}
?>