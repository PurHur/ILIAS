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

include_once("Services/Block/classes/class.ilBlockGUI.php");

/**
* BlockGUI class for Selected Items on Personal Desktop
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @ilCtrl_IsCalledBy ilPDSelectedItemsBlockGUI: ilColumnGUI
*/
class ilPDSelectedItemsBlockGUI extends ilBlockGUI
{
	static $block_type = "pditems";
	
	/**
	* Constructor
	*/
	function ilPDSelectedItemsBlockGUI()
	{
		global $ilCtrl, $lng, $ilUser;
		
		parent::ilBlockGUI();
		
		//$this->setImage(ilUtil::getImagePath("icon_bm_s.gif"));
		$this->setTitle($lng->txt("selected_items"));
		$this->setEnableNumInfo(false);
		$this->setLimit(99999);
		$this->setColSpan(2);
		$this->setAvailableDetailLevels(3, 1);
		$this->setBigMode(true);
		$this->lng = $lng;
		
	}
	
	/**
	* Get block type
	*
	* @return	string	Block type.
	*/
	function getBlockType()
	{
		return self::$block_type;
	}

	function getHTML()
	{
		global $ilCtrl;
		
		$this->setContent($this->getSelectedItemsBlockHTML());
		$ilCtrl->clearParametersByClass("ilpersonaldesktopgui");
		$ilCtrl->clearParameters($this);
		return parent::getHTML();
	}
	
	function getContent()
	{
		return $this->content;
	}
	
	function setContent($a_content)
	{
		$this->content = $a_content;
	}
	
	/**
	* Fill data section
	*/
	function fillDataSection()
	{
		global $ilUser;
		
		$this->tpl->setVariable("BLOCK_ROW", $this->getContent());
	}
	

	/**
	* block footer
	*/
	function fillFooter()
	{
		global $ilCtrl, $lng, $ilUser;

		$this->setFooterLinks();
		$this->fillFooterLinks();
		$this->tpl->setVariable("FCOLSPAN", $this->getColSpan());
		$this->tpl->setCurrentBlock("block_footer");
		$this->tpl->parseCurrentBlock();
	}

	/**
	* Set footer links.
	*/
	function setFooterLinks()
	{
		global $ilUser, $ilCtrl, $lng;
		
		// by type
		if ($ilUser->getPref("pd_order_items") == 'location')
		{
			$this->addFooterLink( $lng->txt("by_type"),
				$ilCtrl->getLinkTargetByClass("ilpersonaldesktopgui",
				"orderPDItemsByType"),
				$ilCtrl->getLinkTargetByClass("ilpersonaldesktopgui",
				"orderPDItemsByType", "", true),
				"block_".$this->getBlockType()."_".$this->block_id
				);
		}
		else
		{
			$this->addFooterLink($lng->txt("by_type"));
		}

		// by location
		if ($ilUser->getPref("pd_order_items") == 'location')
		{
			$this->addFooterLink($lng->txt("by_location"));
		}
		else
		{
			$this->addFooterLink( $lng->txt("by_location"),
				$ilCtrl->getLinkTargetByClass("ilpersonaldesktopgui",
				"orderPDItemsByLocation"),
				$ilCtrl->getLinkTargetByClass("ilpersonaldesktopgui",
				"orderPDItemsByLocation", "", true),
				"block_".$this->getBlockType()."_".$this->block_id
				);
		}
	}

	/**
	* get selected item block
	*/
	function getSelectedItemsBlockHTML()
	{
		global $ilUser, $rbacsystem, $objDefinition, $ilBench;
		
		$tpl =& $this->newBlockTemplate();
		
		switch ($ilUser->getPref("pd_order_items"))
		{
			case "location":
			$ok = $this->getSelectedItemsPerLocation($tpl);
			break;
			
			default:
			$ok = $this->getSelectedItemsPerType($tpl);
			break;
		}
		
		return $tpl->get();
	}

	/**
	* get selected items per type
	*/
	function getSelectedItemsPerType(&$tpl)
	{
		global $ilUser, $rbacsystem, $objDefinition, $ilBench, $ilSetting;
		
		$output = false;
		$types = array(
		array("title" => $this->lng->txt("objs_cat"), "types" => "cat"),
		array("title" => $this->lng->txt("objs_fold"), "types" => "fold"),
		array("title" => $this->lng->txt("objs_crs"), "types" => "crs"),
		array("title" => $this->lng->txt("objs_grp"), "types" => "grp"),
		array("title" => $this->lng->txt("objs_chat"), "types" => "chat"),
		array("title" => $this->lng->txt("objs_frm"), "types" => "frm"),
		array("title" => $this->lng->txt("learning_resources"),"types" => array("lm", "htlm", "sahs", "dbk")),
		array("title" => $this->lng->txt("objs_glo"), "types" => "glo"),
		array("title" => $this->lng->txt("objs_file"), "types" => "file"),
		array("title" => $this->lng->txt("objs_webr"), "types" => "webr"),
		array("title" => $this->lng->txt("objs_exc"), "types" => "exc"),
		array("title" => $this->lng->txt("objs_tst"), "types" => "tst"),
		array("title" => $this->lng->txt("objs_svy"), "types" => "svy"),
		array("title" => $this->lng->txt("objs_mep"), "types" => "mep"),
		array("title" => $this->lng->txt("objs_qpl"), "types" => "qpl"),
		array("title" => $this->lng->txt("objs_spl"), "types" => "spl"),
		array("title" => $this->lng->txt("objs_icrs"), "types" => "icrs"),
		array("title" => $this->lng->txt("objs_icla"), "types" => "icla")
		);
		
		foreach ($types as $type)
		{
			$type = $type["types"];
			$title = $type["title"];
			
			$items = $ilUser->getDesktopItems($type);
			$item_html = array();
			
			if ($this->getCurrentDetailLevel() == 3)
			{
				$rel_header = (is_array($type))
				? "th_lres"
				: "th_".$type;
			}
			
			if (count($items) > 0)
			{
				$tstCount = 0;
				$unsetCount = 0;
				$progressCount = 0;
				$unsetFlag = 0;
				$progressFlag = 0;
				$completedFlag = 0;
				if (strcmp($a_type, "tst") == 0) {
					$items = $this->multiarray_sort($items, "used_tries; title");
					foreach ($items as $tst_item) {
						if (!isset($tst_item["used_tries"])) {
							$unsetCount++;
						}
						elseif ($tst_item["used_tries"] == 0) {
							$progressCount++;
						}
					}
				}
				
				foreach($items as $item)
				{
					// get list gui class for each object type
					if ($cur_obj_type != $item["type"])
					{
						$class = $objDefinition->getClassName($item["type"]);
						$location = $objDefinition->getLocation($item["type"]);
						$full_class = "ilObj".$class."ListGUI";
						include_once($location."/class.".$full_class.".php");
						$item_list_gui = new $full_class();
						$item_list_gui->enableDelete(false);
						$item_list_gui->enableCut(false);
						$item_list_gui->enablePayment(false);
						$item_list_gui->enableLink(false);
						$item_list_gui->enableInfoScreen(false);
						if ($this->getCurrentDetailLevel() < 3)
						{
							$item_list_gui->enableDescription(false);
							$item_list_gui->enableProperties(false);
							$item_list_gui->enablePreconditions(false);
						}
						if ($this->getCurrentDetailLevel() < 2)
						{
							$item_list_gui->enableCommands(true, true);
						}
					}
					// render item row
					$ilBench->start("ilPersonalDesktopGUI", "getListHTML");
					
					$html = $item_list_gui->getListItemHTML($item["ref_id"],
					$item["obj_id"], $item["title"], $item["description"]);
					$ilBench->stop("ilPersonalDesktopGUI", "getListHTML");
					if ($html != "")
					{
						$item_html[] = array("html" => $html, "item_ref_id" => $item["ref_id"],
						"item_obj_id" => $item["obj_id"]);
					}
				}
				
				// output block for resource type
				if (count($item_html) > 0)
				{
					// add a header for each resource type
					if ($this->getCurrentDetailLevel() == 3)
					{
						if ($ilSetting->get("icon_position_in_lists") == "item_rows")
						{
							$this->addHeaderRow($tpl, $type, false);
						}
						else
						{
							$this->addHeaderRow($tpl, $type);
						}
						$this->resetRowType();
					}
					
					// content row
					foreach($item_html as $item)
					{
						if ($this->getCurrentDetailLevel() < 3 ||
						$ilSetting->get("icon_position_in_lists") == "item_rows")
						{
							$this->addStandardRow($tpl, $item["html"], $item["item_ref_id"], $item["item_obj_id"], $type, $rel_header);
						}
						else
						{
							$this->addStandardRow($tpl, $item["html"], $item["item_ref_id"], $item["item_obj_id"], "", $rel_header);
						}
						$output = true;
					}
				}
			}
		}
		
		return $output;
	}
	
	/**
	* get selected items per type
	*/
	function getSelectedItemsPerLocation(&$tpl)
	{
		global $ilUser, $rbacsystem, $objDefinition, $ilBench, $ilSetting;
		
		$output = false;
		
		$items = $ilUser->getDesktopItems();
		$item_html = array();
		
		if (count($items) > 0)
		{
			foreach($items as $item)
			{
				//echo "1";
				// get list gui class for each object type
				if ($cur_obj_type != $item["type"])
				{
					$item_list_gui =& $this->getItemListGUI($item["type"]);
					
					$item_list_gui->enableDelete(false);
					$item_list_gui->enableCut(false);
					$item_list_gui->enablePayment(false);
					$item_list_gui->enableLink(false);
					$item_list_gui->enableInfoScreen(false);
					if ($this->getCurrentDetailLevel() < 3)
					{
						//echo "3";
						$item_list_gui->enableDescription(false);
						$item_list_gui->enableProperties(false);
						$item_list_gui->enablePreconditions(false);
					}
					if ($this->getCurrentDetailLevel() < 2)
					{
						$item_list_gui->enableCommands(true, true);
					}
				}
				// render item row
				$ilBench->start("ilPersonalDesktopGUI", "getListHTML");
				
				$html = $item_list_gui->getListItemHTML($item["ref_id"],
				$item["obj_id"], $item["title"], $item["description"]);
				$ilBench->stop("ilPersonalDesktopGUI", "getListHTML");
				if ($html != "")
				{
					$item_html[] = array("html" => $html, "item_ref_id" => $item["ref_id"],
					"item_obj_id" => $item["obj_id"], "parent_ref" => $item["parent_ref"],
					"type" => $item["type"]);
				}
			}
			
			// output block for resource type
			if (count($item_html) > 0)
			{
				$cur_parent_ref = 0;
				
				// content row
				foreach($item_html as $item)
				{
					// add a parent header row for each new parent
					if ($cur_parent_ref != $item["parent_ref"])
					{
						if ($ilSetting->get("icon_position_in_lists") == "item_rows")
						{
							$this->addParentRow($tpl, $item["parent_ref"], false);
						}
						else
						{
							$this->addParentRow($tpl, $item["parent_ref"]);
						}
						$this->resetRowType();
						$cur_parent_ref = $item["parent_ref"];
					}
					
					//if ($ilUser->getPref("pd_selected_items_details") != "y" ||
					//	$this->ilias->getSetting("icon_position_in_lists") == "item_rows")
					//{
						$this->addStandardRow($tpl, $item["html"], $item["item_ref_id"], $item["item_obj_id"], $item["type"],
						"th_".$cur_parent_ref);
					//}
					//else
					//{
						//	$this->addStandardRow($tpl, $item["html"], $item["item_ref_id"], $item["item_obj_id"]);
					//}
					$output = true;
				}
			}
		}
		
		return $output;
	}

		function resetRowType()
	{
		$this->cur_row_type = "";
	}
	
	/**
	* returns a new list block template
	*
	* @access	private
	* @return	object		block template
	*/
	function &newBlockTemplate()
	{
		$tpl = new ilTemplate ("tpl.pd_list_block.html", true, true);
		$this->cur_row_type = "";
		return $tpl;
	}

	/**
	* get item list gui class for type
	*/
	function &getItemListGUI($a_type)
	{
		global $objDefinition;
		//echo "<br>+$a_type+";
		if (!is_object($this->item_list_guis[$a_type]))
		{
			$class = $objDefinition->getClassName($a_type);
			$location = $objDefinition->getLocation($a_type);
			$full_class = "ilObj".$class."ListGUI";
			//echo "<br>-".$location."/class.".$full_class.".php"."-";
			include_once($location."/class.".$full_class.".php");
			$item_list_gui = new $full_class();
			$this->item_list_guis[$a_type] =& $item_list_gui;
		}
		else
		{
			$item_list_gui =& $this->item_list_guis[$a_type];
		}
		return $item_list_gui;
	}
	
	/**
	* adds a header row to a block template
	*
	* @param	object		$a_tpl		block template
	* @param	string		$a_type		object type
	* @access	private
	*/
	function addHeaderRow(&$a_tpl, $a_type, $a_show_image = true)
	{
		if (!is_array($a_type))
		{
			$icon = ilUtil::getImagePath("icon_".$a_type.".gif");
			$title = $this->lng->txt("objs_".$a_type);
			$header_id = "th_".$a_type;
		}
		else
		{
			$icon = ilUtil::getImagePath("icon_lm.gif");
			$title = $this->lng->txt("learning_resources");
			$header_id = "th_lres";
		}
		if ($a_show_image)
		{
			$a_tpl->setCurrentBlock("container_header_row_image");
			$a_tpl->setVariable("HEADER_IMG", $icon);
			$a_tpl->setVariable("HEADER_ALT", $title);
		}
		else
		{
			$a_tpl->setCurrentBlock("container_header_row");
		}
		
		$a_tpl->setVariable("BLOCK_HEADER_CONTENT", $title);
		$a_tpl->setVariable("BLOCK_HEADER_ID", $header_id);
		$a_tpl->parseCurrentBlock();
		$a_tpl->touchBlock("container_row");
	}
	
	/**
	* adds a header row to a block template
	*
	* @param	object		$a_tpl		block template
	* @param	string		$a_type		object type
	* @access	private
	*/
	function addParentRow(&$a_tpl, $a_ref_id, $a_show_image = true)
	{
		global $tree, $ilSetting;
		
		$par_id = ilObject::_lookupObjId($a_ref_id);
		$type = ilObject::_lookupType($par_id);
		if (!in_array($type, array("lm", "dbk", "sahs", "htlm")))
		{
			$icon = ilUtil::getImagePath("icon_".$type.".gif");
		}
		else
		{
			$icon = ilUtil::getImagePath("icon_lm.gif");
		}
		
		// custom icon
		if ($ilSetting->get("custom_icons") &&
		in_array($type, array("cat","grp","crs", "root")))
		{
			require_once("classes/class.ilContainer.php");
			if (($path = ilContainer::_lookupIconPath($par_id, "small")) != "")
			{
				$icon = $path;
			}
		}
		
		if ($tree->getRootId() != $par_id)
		{
			$title = ilObject::_lookupTitle($par_id);
		}
		else
		{
			$nd = $tree->getNodeData(ROOT_FOLDER_ID);
			$title = $nd["title"];
			if ($title == "ILIAS")
			{
				$title = $this->lng->txt("repository");
			}
		}
		
		$item_list_gui =& $this->getItemListGUI($type);
		
		$item_list_gui->enableDelete(false);
		$item_list_gui->enableCut(false);
		$item_list_gui->enablePayment(false);
		$item_list_gui->enableLink(false);
		$item_list_gui->enableDescription(false);
		$item_list_gui->enableProperties(false);
		$item_list_gui->enablePreconditions(false);
		$item_list_gui->enablePath(true);
		$item_list_gui->enableCommands(false);
		$html = $item_list_gui->getListItemHTML($a_ref_id,
		$par_id, $title, "");
		
		if ($a_show_image)
		{
			$a_tpl->setCurrentBlock("container_header_row_image");
			$a_tpl->setVariable("HEADER_IMG", $icon);
			$a_tpl->setVariable("HEADER_ALT", $title);
		}
		else
		{
			$a_tpl->setCurrentBlock("container_header_row");
		}
		
		$a_tpl->setVariable("BLOCK_HEADER_CONTENT", $html);
		$a_tpl->setVariable("BLOCK_HEADER_ID", "th_".$a_ref_id);
		$a_tpl->parseCurrentBlock();
		$a_tpl->touchBlock("container_row");
	}
	
	/**
	* adds a standard row to a block template
	*
	* @param	object		$a_tpl		block template
	* @param	string		$a_html		html code
	* @access	private
	*/
	function addStandardRow(&$a_tpl, $a_html, $a_item_ref_id = "", $a_item_obj_id = "",
	$a_image_type = "", $a_related_header = "")
	{
		global $ilSetting;
		
		$this->cur_row_type = ($this->cur_row_type == "row_type_1")
		? "row_type_2"
		: "row_type_1";
		$a_tpl->touchBlock($this->cur_row_type);
		
		if ($a_image_type != "")
		{
			if (!is_array($a_image_type) && !in_array($a_image_type, array("lm", "dbk", "htlm", "sahs")))
			{
				$icon = ilUtil::getImagePath("icon_".$a_image_type.".gif");
				$title = $this->lng->txt("obj_".$a_image_type);
			}
			else
			{
				$icon = ilUtil::getImagePath("icon_lm.gif");
				$title = $this->lng->txt("learning_resource");
			}
			
			// custom icon
			if ($ilSetting->get("custom_icons") &&
			in_array($a_image_type, array("cat","grp","crs")))
			{
				require_once("classes/class.ilContainer.php");
				if (($path = ilContainer::_lookupIconPath($a_item_obj_id, "small")) != "")
				{
					$icon = $path;
				}
			}
			
			$a_tpl->setCurrentBlock("block_row_image");
			$a_tpl->setVariable("ROW_IMG", $icon);
			$a_tpl->setVariable("ROW_ALT", $title);
			$a_tpl->parseCurrentBlock();
		}
		else
		{
			$a_tpl->setVariable("ROW_NBSP", "&nbsp;");
		}
		$a_tpl->setCurrentBlock("container_standard_row");
		$a_tpl->setVariable("BLOCK_ROW_CONTENT", $a_html);
		$rel_headers = ($a_related_header != "")
		? "th_selected_items ".$a_related_header
		: "th_selected_items";
		$a_tpl->setVariable("BLOCK_ROW_HEADERS", $rel_headers);
		$a_tpl->parseCurrentBlock();
		$a_tpl->touchBlock("container_row");
	}

	/**
	* Get overview.
	*/
	function getOverview()
	{
		global $ilUser, $lng, $ilCtrl;
				
		return '<div class="small">'.$this->num_bookmarks." ".$lng->txt("bm_num_bookmarks").", ".
			$this->num_folders." ".$lng->txt("bm_num_bookmark_folders")."</div>";
	}

}

?>
