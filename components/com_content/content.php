<?php
/**
 * @version $Id$
 * @package Joomla
 * @subpackage Content
 * @copyright Copyright (C) 2005 Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant to the
 * GNU General Public License, and as distributed it includes or is derivative
 * of works licensed under the GNU General Public License or other free or open
 * source software licenses. See COPYRIGHT.php for copyright notices and
 * details.
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

// require the component helper 
require_once (JApplicationHelper::getPath('helper', 'com_content'));

// require the html view class
require_once (JApplicationHelper::getPath('front_html', 'com_content'));

/*
 * Special case if we are on the frontpage [index.php of the site]
 */
if ($option == 'com_frontpage')
{
	JContentController::frontpage();
	return;
}

switch (strtolower($task))
{
	case 'section' :
		JContentController::showSection();
		break;

	case 'category' :
		JContentController::showCategory();
		break;

	case 'blogsection' :
		JContentController::showBlogSection();
		break;

	case 'blogcategorymulti' :
	case 'blogcategory' :
		JContentController::showBlogCategory();
		break;

	case 'archivesection' :
		JContentController::showArchiveSection();
		break;

	case 'archivecategory' :
		JContentController::showArchiveCategory();
		break;

	case 'view' :
		JContentController::showItem();
		break;

	case 'viewpdf' :
		JContentController::showItemAsPDF();
		break;

	case 'edit' :
	case 'new' :
		JContentController::editItem();
		break;

	case 'save' :
	case 'apply' :
	case 'apply_new' :
		$cache = & JFactory::getCache();
		$cache->cleanCache('com_content');
		JContentController::saveContent();
		break;

	case 'cancel' :
		JContentController::cancelContent();
		break;

	case 'emailform' :
		JContentController::emailContentForm();
		break;

	case 'emailsend' :
		JContentController::emailContentSend();
		break;

	case 'vote' :
		JContentController::recordVote();
		break;

	case 'findkey' :
		JContentController::findKeyItem();
		break;

	default :
		// Tried to access an unknown task
		JError::raiseError( 404, JText::_("Resource Not Found") );
		break;
}

/**
 * Content Component Controller
 *
 * @static
 * @package Joomla
 * @subpackage Content
 * @since 1.1
 */
class JContentController
{
	function frontpage()
	{
		global $mainframe, $Itemid;

		/*
		 * Initialize some variables
		 */
		$db			= & $mainframe->getDBO();
		$user		= & $mainframe->getUser();
		$gid			= $user->get('gid');

		/*
		 * Create a user access object for the user
		 */
		$access							= new stdClass();
		$access->canEdit			= $user->authorize('action', 'edit', 'content', 'all');
		$access->canEditOwn		= $user->authorize('action', 'edit', 'content', 'own');
		$access->canPublish		= $user->authorize('action', 'publish', 'content', 'all');

		// Parameters
		$menu = JMenu::getInstance();
		$menu = $menu->getItem($Itemid);
		$params = new JParameter($menu->params);

		require_once (dirname(__FILE__).DS.'model'.DS.'frontpage.php');
		$model = new JModelFrontpage($db, $params);
		
		// Dynamic Page Title
		$mainframe->SetPageTitle($menu->name);

		$cache = & JFactory::getCache('com_content');
		$cache->call('JContentViewHTML::showBlog', $model, $params, $access, $menu);
	}

	/**
	 * Method to build data for displaying a content section
	 *
	 * @static
	 * @since 1.0
	 */
	function showSection()
	{
		global $mainframe, $Itemid;

		/*
		 * Initialize some variables
		 */
		$db				= & $mainframe->getDBO();
		$user			= & $mainframe->getUser();
		$noauth		= !$mainframe->getCfg('shownoauth');
		$now			= $mainframe->get('requestTime');
		$nullDate		= $db->getNullDate();
		$gid				= $user->get('gid');
		$id				= JRequest::getVar('id', 0, '', 'int');

		/*
		 * Create a user access object for the user
		 */
		$access							= new stdClass();
		$access->canEdit			= $user->authorize('action', 'edit', 'content', 'all');
		$access->canEditOwn		= $user->authorize('action', 'edit', 'content', 'own');
		$access->canPublish		= $user->authorize('action', 'publish', 'content', 'all');

		/*
		 * I ABSOLUTELY HATE THIS.... But, menu parameters are coupled to the
		 * display of a category and/or content item.... crazy isn't it?
		 * Anyway, lets do the obligatory getting of menu params and setting
		 * them if they exist
		 */
		if ($Itemid)
		{
			$menu = JMenu::getInstance();
			$menu = $menu->getItem($Itemid);
			$params = new JParameter($menu->params);
		}
		else
		{
			$menu = null;
			$params = new JParameter();
		}

		// Set the display type parameter
		$params->set('type', 'section');

		// Set some parameter defaults
		$params->def('page_title', 1);
		$params->def('pageclass_sfx', '');
		$params->def('other_cat_section', 1);
		$params->def('empty_cat_section', 0);
		$params->def('other_cat', 1);
		$params->def('empty_cat', 0);
		$params->def('cat_items', 1);
		$params->def('cat_description', 1);
		$params->def('back_button', $mainframe->getCfg('back_button'));
		$params->def('pageclass_sfx', '');


		/*
		 * Lets set the page title
		 */
		if (!empty ($menu->name))
		{
			$mainframe->setPageTitle($menu->name);
		}

		require_once (dirname(__FILE__).DS.'model'.DS.'section.php');
		$model = new JModelSection($db, $params, $id);

		$cache = & JFactory::getCache('com_content');
		$cache->call('JContentViewHTML::showSection', $model, $params);
	}

	/**
	 * Method to build data for displaying a content section
	 *
	 * @static
	 * @since 1.0
	 */
	function showCategory()
	{
		global $mainframe, $Itemid;

		/*
		 * Initialize some variables
		 */
		$db						= & $mainframe->getDBO();
		$user					= & $mainframe->getUser();
		$id						= JRequest::getVar('id', 0, '', 'int');
		$filter					= JRequest::getVar('filter', '', 'request');
		$filter_order		= JRequest::getVar('filter_order');
		$filter_order_Dir	= JRequest::getVar('filter_order_Dir');

		/*
		 * Create a user access object for the user
		 */
		$access							= new stdClass();
		$access->canEdit			= $user->authorize('action', 'edit', 'content', 'all');
		$access->canEditOwn		= $user->authorize('action', 'edit', 'content', 'own');
		$access->canPublish		= $user->authorize('action', 'publish', 'content', 'all');

		/*
		 * I HATE that menu item parameters are coupled so closely with the
		 * view... but alas... they are, so lets get them
		 */
		if ($Itemid)
		{
			$menu = JMenu::getInstance();
			$menu = $menu->getItem($Itemid);
			$params = new JParameter($menu->params);
			$pagetitle = $menu->name;
		}
		else
		{
			$menu = null;
			$params = new JParameter();
			$pagetitle = null;
		}

		$params->def('page_title',				1);
		$params->def('title',							1);
		$params->def('hits',							$mainframe->getCfg('hits'));
		$params->def('author',					!$mainframe->getCfg('hideAuthor'));
		$params->def('date',						!$mainframe->getCfg('hideCreateDate'));
		$params->def('date_format',			JText::_('DATE_FORMAT_LC'));
		$params->def('navigation',				2);
		$params->def('display',					1);
		$params->def('display_num',			$mainframe->getCfg('list_limit'));
		$params->def('other_cat',				1);
		$params->def('empty_cat',			0);
		$params->def('cat_items',				1);
		$params->def('cat_description',	0);
		$params->def('back_button',			$mainframe->getCfg('back_button'));
		$params->def('pageclass_sfx',		'');
		$params->def('headings',				1);
		$params->def('filter',						1);
		$params->def('filter_type',				'title');


		// Dynamic Page Title
		// TODO: fix this... move to view and pass proper data
		$mainframe->SetPageTitle($pagetitle);


		/*
		 * Table ordering values
		 */
		$lists['task'] = 'category';
		$lists['filter'] = $filter;
		if ($filter_order_Dir == 'DESC')
		{
			$lists['order_Dir'] = 'ASC';
		}
		else
		{
			$lists['order_Dir'] = 'DESC';
		}
		$lists['order'] = $filter_order;
		$selected = '';

		require_once (dirname(__FILE__).DS.'model'.DS.'category.php');
		$model = new JModelCategory($db, $params, $id);
		
		JContentViewHTML::showCategory($model, $access, $params, $lists, $selected);
	}

	function showBlogSection()
	{
		global $mainframe, $Itemid;

		/*
		 * Initialize some variables
		 */
		$db			= & $mainframe->getDBO();
		$user		= & $mainframe->getUser();
		$id			= JRequest::getVar('id', 0, '', 'int');

		/*
		 * Create a user access object for the user
		 */
		$access							= new stdClass();
		$access->canEdit			= $user->authorize('action', 'edit', 'content', 'all');
		$access->canEditOwn		= $user->authorize('action', 'edit', 'content', 'own');
		$access->canPublish		= $user->authorize('action', 'publish', 'content', 'all');

		// Parameters
		if ($Itemid)
		{
			$menu = JMenu::getInstance();
			$menu = $menu->getItem($Itemid);
			$params = new JParameter($menu->params);
		}
		else
		{
			$menu		= null;
			$params	= new JParameter(null);
		}

		// Dynamic Page Title and BreadCrumbs
		$breadcrumbs = & $mainframe->getPathWay();
		if ($menu->name)
		{
			$breadcrumbs->addItem($menu->name, '');
			$mainframe->setPageTitle($menu->name);
		}
		else
		{
			$breadcrumbs->addItem($rows[0]->section, '');
		}

		require_once (dirname(__FILE__).DS.'model'.DS.'section.php');
		$model = new JModelSection($db, $params, $id);
		
		$cache = & JFactory::getCache('com_content');
		$cache->call('JContentViewHTML::showBlog', $model, $params, $access, $menu);
	}

	function showBlogCategory()
	{
		global $mainframe, $Itemid;

		/*
		 * Initialize variables
		 */
		$user		= & $mainframe->getUser();
		$id			= JRequest::getVar('id', 0, '', 'int');

		/*
		 * Create a user access object for the user
		 */
		$access							= new stdClass();
		$access->canEdit			= $user->authorize('action', 'edit', 'content', 'all');
		$access->canEditOwn		= $user->authorize('action', 'edit', 'content', 'own');
		$access->canPublish		= $user->authorize('action', 'publish', 'content', 'all');

		// Paramters
		if ($Itemid)
		{
			$menu = JMenu::getInstance();
			$menu = $menu->getItem($Itemid);
			$params = new JParameter($menu->params);
		}
		else
		{
			$menu = null;
			$params = new JParameter();
		}

		// Dynamic Page Title and BreadCrumbs
		$breadcrumbs = & $mainframe->getPathWay();
		$document = & $mainframe->getDocument();
		if ($menu->name)
		{
			$document->setTitle($menu->name);
			$breadcrumbs->addItem($menu->name, '');
		}
		else
		{
			$breadcrumbs->addItem($rows[0]->section, '');
		}

		require_once (dirname(__FILE__).DS.'model'.DS.'blog.php');
		$rows = & JContentBlog::getCategoryData($id, $access, $params);
		
		$cache = & JFactory::getCache('com_content');
		$cache->call('JContentViewHTML::showBlog', $rows, $params, $access, $menu);
	}

	function showArchiveSection()
	{
		global $mainframe, $Itemid;

		/*
		 * Initialize some variables
		 */
		$user		= & $mainframe->getUser();
		$id			= JRequest::getVar('id', 0, '', 'int');

		/*
		 * Create a user access object for the user
		 */
		$access							= new stdClass();
		$access->canEdit			= $user->authorize('action', 'edit', 'content', 'all');
		$access->canEditOwn		= $user->authorize('action', 'edit', 'content', 'own');
		$access->canPublish		= $user->authorize('action', 'publish', 'content', 'all');

		if ($Itemid)
		{
			$menu = JMenu::getInstance();
			$menu = $menu->getItem($Itemid);
			$params = new JParameter($menu->params);
		}
		else
		{
			$menu = null;
			$params = new JParameter();
		}


		// Dynamic Page Title
		$mainframe->SetPageTitle($menu->name);

		// Append Archives to BreadCrumbs
		$breadcrumbs = & $mainframe->getPathWay();
		$breadcrumbs->addItem('Archives', '');


		require_once (dirname(__FILE__).DS.'model'.DS.'archive.php');
		$rows = & JContentArchive::getSectionData($id, $access, $params);

		if (!$rows)
		{
			JContentViewHTML::emptyContainer(JText::_('CATEGORY_ARCHIVE_EMPTY'));
		}
		else
		{
			$cache = & JFactory::getCache('com_content');
			$cache->call('JContentViewHTML::showArchive', $rows, $params, $access, $menu, $id);
		}
	}

	function showArchiveCategory()
	{
		global $mainframe, $Itemid;

		// Parameters
		$user		= & $mainframe->getUser();
		$id			= JRequest::getVar( 'id', 0, '', 'int' );

		/*
		 * Create a user access object for the user
		 */
		$access							= new stdClass();
		$access->canEdit			= $user->authorize('action', 'edit', 'content', 'all');
		$access->canEditOwn		= $user->authorize('action', 'edit', 'content', 'own');
		$access->canPublish		= $user->authorize('action', 'publish', 'content', 'all');

		if ($Itemid)
		{
			$menu = JMenu::getInstance();
			$menu = $menu->getItem($Itemid);
			$params = new JParameter($menu->params);
		}
		else
		{
			$menu = null;
			$params = new JParameter();
		}

		// Page Title
		$mainframe->SetPageTitle($menu->name);

		// Append Archives to BreadCrumbs
		$breadcrumbs = & $mainframe->getPathWay();
		$breadcrumbs->addItem('Archives', '');

		require_once (dirname(__FILE__).DS.'model'.DS.'archive.php');
		$rows = & JContentArchive::getCategoryData($id, $access, $params);

		if (!$rows)
		{
			JContentViewHTML::emptyContainer(JText::_('CATEGORY_ARCHIVE_EMPTY'));
		}
		else
		{
			JContentViewHTML::showArchive($rows, $params, $access, $menu, $id);
		}
	}

	/**
	 * Method to show a content item as the main page display
	 *
	 * @static
	 * @return void
	 * @since 1.0
	 */
	function showItem()
	{
		global $mainframe, $Itemid;

		/*
		 * Initialize variables
		 */
		$db			= & $mainframe->getDBO();
		$user		= & $mainframe->getUser();
		$MetaTitle	= $mainframe->getCfg('MetaTitle');
		$MetaAuthor	= $mainframe->getCfg('MetaAuthor');
		$voting		= $mainframe->getCfg('vote');
		$now		= $mainframe->get('requestTime');
		$noauth		= !$mainframe->getCfg('shownoauth');
		$nullDate	= $db->getNullDate();
		$gid		= $user->get('gid');
		$option		= JRequest::getVar('option');
		$uid		= JRequest::getVar('id',			0, '', 'int');
		$pop		= JRequest::getVar('pop',			0, '', 'int');
		$limit		= JRequest::getVar('limit',			0, '', 'int');
		$limitstart	= JRequest::getVar('limitstart',	0, '', 'int');
		$row		= null;

		/*
		 * Create a user access object for the user
		 */
		$access							= new stdClass();
		$access->canEdit			= $user->authorize('action', 'edit', 'content', 'all');
		$access->canEditOwn		= $user->authorize('action', 'edit', 'content', 'own');
		$access->canPublish		= $user->authorize('action', 'publish', 'content', 'all');

		/*
		 * Get a content component cache object
		 */
		$cache = & JFactory::getCache('com_content');

		if ($access->canEdit)
		{
			$xwhere = '';
		}
		else
		{
			$xwhere = " AND ( a.state = 1 OR a.state = -1 )" .
					"\n AND ( a.publish_up = '$nullDate' OR a.publish_up <= '$now' )" .
					"\n AND ( a.publish_down = '$nullDate' OR a.publish_down >= '$now' )";
		}

		$voting = JContentHelper::buildVotingQuery();

		// Main content item query
		$query = "SELECT a.*, u.name AS author, u.usertype, cc.title AS category, s.title AS section," .
				"\n g.name AS groups, s.published AS sec_pub, cc.published AS cat_pub, s.access AS sec_access, cc.access AS cat_access".$voting['select'].
				"\n FROM #__content AS a" .
				"\n LEFT JOIN #__categories AS cc ON cc.id = a.catid" .
				"\n LEFT JOIN #__sections AS s ON s.id = cc.section AND s.scope = 'content'" .
				"\n LEFT JOIN #__users AS u ON u.id = a.created_by" .
				"\n LEFT JOIN #__groups AS g ON a.access = g.id".
				$voting['join'].
				"\n WHERE a.id = $uid".
				$xwhere.
				"\n AND a.access <= $gid";
		$db->setQuery($query);

		if ($db->loadObject($row))
		{
			if (!$row->cat_pub && $row->catid)
			{
				// check whether category is published
				JError::raiseError( 404, JText::_("Resource Not Found") );
			}
			if (!$row->sec_pub && $row->sectionid)
			{
				// check whether section is published
				JError::raiseError( 404, JText::_("Resource Not Found") );
			}
			if (($row->cat_access > $gid) && $row->catid)
			{
				// check whether category access level allows access
				JError::raiseError( 403, JText::_("Access Forbidden") );
			}
			if (($row->sec_access > $gid) && $row->sectionid)
			{
				// check whether section access level allows access
				JError::raiseError( 403, JText::_("Access Forbidden") );
			}

			$params = new JParameter($row->attribs);
			$params->set('intro_only', 0);
			$params->def('back_button', $mainframe->getCfg('back_button'));
			if ($row->sectionid == 0)
			{
				$params->set('item_navigation', 0);
			}
			else
			{
				$params->set('item_navigation', $mainframe->getCfg('item_navigation'));
			}
			if ($MetaTitle == '1')
			{
				$mainframe->addMetaTag('title', $row->title);
			}
			if ($MetaAuthor == '1')
			{
				$mainframe->addMetaTag('author', $row->author);
			}

			/*
			 * Handle BreadCrumbs and Page Title
			 */
			$breadcrumbs = & $mainframe->getPathWay();
			if (!empty ($Itemid))
			{
				// Section
				if (!empty ($row->section))
				{
					$breadcrumbs->addItem($row->section, sefRelToAbs('index.php?option=com_content&amp;task=section&amp;id='.$row->sectionid.'&amp;Itemid='.$Itemid));
				}
				// Category
				if (!empty ($row->section))
				{
					$breadcrumbs->addItem($row->category, sefRelToAbs('index.php?option=com_content&amp;task=category&amp;sectionid='.$row->sectionid.'&amp;id='.$row->catid.'&amp;Itemid='.$Itemid));
				}
			}
			// Item
			$breadcrumbs->addItem($row->title, '');
			$mainframe->setPageTitle($row->title);

			if ($access->canEdit)
			{
				if ($row->id === null || $row->access > $gid)
				{
					JError::raiseError( 404, JText::_("Resource Not Found") );
				}
			}
			else
			{
				if ($row->id === null || $row->state == 0)
				{
					JError::raiseError( 404, JText::_("Resource Not Found") );
				}
				if ($row->access > $gid)
				{
					if ($noauth)
					{
						JError::raiseError( 403, JText::_("Access Forbidden") );
					}
					else
					{
						if (!($params->get('intro_only')))
						{
							JError::raiseError( 403, JText::_("Access Forbidden") );
						}
					}
				}
			}

			/*
			 * Get some parameters from global configuration
			 */
			$params->def('link_titles',		$mainframe->getCfg('link_titles'));
			$params->def('author',			!$mainframe->getCfg('hideAuthor'));
			$params->def('createdate',	!$mainframe->getCfg('hideCreateDate'));
			$params->def('modifydate',	!$mainframe->getCfg('hideModifyDate'));
			$params->def('print',				!$mainframe->getCfg('hidePrint'));
			$params->def('pdf',					!$mainframe->getCfg('hidePdf'));
			$params->def('email',				!$mainframe->getCfg('hideEmail'));
			$params->def('rating',				$mainframe->getCfg('vote'));
			$params->def('icons',				$mainframe->getCfg('icons'));
			$params->def('readmore',		$mainframe->getCfg('readmore'));
			
			/*
			 * Get some item specific parameters
			 */
			$params->def('image',					1);
			$params->def('section',				0);
			$params->def('popup',					$pop);
			$params->def('section_link',		0);
			$params->def('category',			0);
			$params->def('category_link',	0);
			$params->def('introtext',			1);
			$params->def('pageclass_sfx',	'');
			$params->def('item_title',			1);
			$params->def('url',						1);
	
			if ($params->get('section_link') && $row->sectionid)
			{
				$row->section = JContentHelper::getSectionLink($row);
			}
	
			if ($params->get('category_link') && $row->catid)
			{
				$row->category = JContentHelper::getCategoryLink($row);
			}
	
			// show/hides the intro text
			if ($params->get('introtext'))
			{
				$row->text = $row->introtext. ($params->get('intro_only') ? '' : chr(13).chr(13).$row->fulltext);
			}
			else
			{
				$row->text = $row->fulltext;
			}
	
			// record the hit
			if (!$params->get('intro_only') && ($limitstart == 0))
			{
				$obj = & JTable::getInstance('content', $db);
				$obj->hit($row->id);
			}
	
			$cache = & JFactory::getCache('com_content');
			$cache->call('JContentViewHTML::showItem', $row, $params, $access, $limitstart);
		}
		else
		{
			JError::raiseError( 404, JText::_("Resource Not Found") );
		}
	}

	function showItemAsPDF()
	{
		require_once (dirname(__FILE__).DS.'content.pdf.php');
		JContentViewPDF::showItem();
	}

	function editItem()
	{
		global $mainframe, $Itemid;

		/*
		 * Initialize variables
		 */
		$db				= & $mainframe->getDBO();
		$user			= & $mainframe->getUser();
		$breadcrumbs	= & $mainframe->getPathWay();
		$nullDate		= $db->getNullDate();
		$uid			= JRequest::getVar('id', 0, '', 'int');
		$sectionid		= JRequest::getVar('sectionid', 0, '', 'int');
		$task			= JRequest::getVar('task');

		/*
		 * Create a user access object for the user
		 */
		$access					= new stdClass();
		$access->canEdit		= $user->authorize('action', 'edit', 'content', 'all');
		$access->canEditOwn		= $user->authorize('action', 'edit', 'content', 'own');
		$access->canPublish		= $user->authorize('action', 'publish', 'content', 'all');

		/*
		 * Get the content data object
		 */
		$row = & JTable::getInstance('content', $db);
		$row->load($uid);

		// fail if checked out not by 'me'
		if ($row->isCheckedOut($user->get('id')))
		{
			JContentViewHTML::userInputError(JText::_('The module')." [ ".$row->title." ] ".JText::_('DESCBEINGEDITTEDBY'));
		}

		if ($uid)
		{
			// existing record
			if (!($access->canEdit || ($access->canEditOwn && $row->created_by == $user->get('gid'))))
			{
				JError::raiseError( 403, JText::_("Access Forbidden") );
			}
		}
		else
		{
			// new record
			if (!($access->canEdit || $access->canEditOwn))
			{
				JError::raiseError( 403, JText::_("Access Forbidden") );
			}
		}

		if ($uid)
		{
			$sectionid = $row->sectionid;
		}

		$lists = array ();

		// get the type name - which is a special category
		$query = "SELECT name FROM #__sections" .
				"\n WHERE id = $sectionid";
		$db->setQuery($query);
		$section = $db->loadResult();

		if ($uid == 0)
		{
			$row->catid = 0;
		}

		if ($uid)
		{
			$row->checkout($user->get('id'));
			if (trim($row->publish_down) == $nullDate)
			{
				$row->publish_down = 'Never';
			}
			if (trim($row->images))
			{
				$row->images = explode("\n", $row->images);
			}
			else
			{
				$row->images = array ();
			}
			$query = "SELECT name" .
					"\n FROM #__users" .
					"\n WHERE id = $row->created_by";
			$db->setQuery($query);
			$row->creator = $db->loadResult();

			// test to reduce unneeded query
			if ($row->created_by == $row->modified_by)
			{
				$row->modifier = $row->creator;
			}
			else
			{
				$query = "SELECT name" .
						"\n FROM #__users" .
						"\n WHERE id = $row->modified_by";
				$db->setQuery($query);
				$row->modifier = $db->loadResult();
			}

			$query = "SELECT content_id" .
					"\n FROM #__content_frontpage" .
					"\n WHERE content_id = $row->id";
			$db->setQuery($query);
			$row->frontpage = $db->loadResult();

			$title = JText::_('Edit');
		}
		else
		{
			$row->sectionid			= $sectionid;
			$row->version				= 0;
			$row->state				= 0;
			$row->ordering			= 0;
			$row->images				= array ();
			$row->publish_up		= date('Y-m-d', time());
			$row->publish_down	= 'Never';
			$row->creator				= 0;
			$row->modifier			= 0;
			$row->frontpage		= 0;

			$title = JText::_('New');
		}

		// calls function to read image from directory
		$pathA			= 'images/stories';
		$pathL			= 'images/stories';
		$images		= array ();
		$folders		= array ();
		$folders[]		= mosHTML::makeOption('/');
		mosAdminMenus::ReadImages($pathA, '/', $folders, $images);
		// list of folders in images/stories/
		$lists['folders'] = mosAdminMenus::GetImageFolders($folders, $pathL);
		// list of images in specfic folder in images/stories/
		$lists['imagefiles'] = mosAdminMenus::GetImages($images, $pathL);
		// list of saved images
		$lists['imagelist'] = mosAdminMenus::GetSavedImages($row, $pathL);

		// build the html select list for ordering
		$query = "SELECT ordering AS value, title AS text" .
				"\n FROM #__content" .
				"\n WHERE catid = $row->catid" .
				"\n ORDER BY ordering";
		$lists['ordering'] = mosAdminMenus::SpecificOrdering($row, $uid, $query, 1);

		// build list of categories
		$lists['catid'] = mosAdminMenus::ComponentCategory('catid', $sectionid, intval($row->catid));
		// build the select list for the image positions
		$lists['_align'] = mosAdminMenus::Positions('_align');
		// build the html select list for the group access
		$lists['access'] = mosAdminMenus::Access($row);

		// build the select list for the image caption alignment
		$lists['_caption_align'] = mosAdminMenus::Positions('_caption_align');
		// build the html select list for the group access
		// build the select list for the image caption position
		$pos[] = mosHTML::makeOption('bottom', JText::_('Bottom'));
		$pos[] = mosHTML::makeOption('top', JText::_('Top'));
		$lists['_caption_position'] = mosHTML::selectList($pos, '_caption_position', 'class="inputbox" size="1"', 'value', 'text');

		// build the html radio buttons for published
		$lists['state'] = mosHTML::yesnoradioList('state', '', $row->state);
		// build the html radio buttons for frontpage
		$lists['frontpage'] = mosHTML::yesnoradioList('frontpage', '', $row->frontpage);

		$title = $title.' '.JText::_('Content');

		// Set page title
		$mainframe->setPageTitle($title);

		// Add pathway item
		$breadcrumbs->addItem($title, '');

		JContentViewHTML::editContent($row, $section, $lists, $images, $access, $user->get('id'), $sectionid, $task, $Itemid);
	}

	/**
	* Saves the content item an edit form submit
	*/
	function saveContent()
	{
		global $mainframe, $Itemid;

		/*
		 * Initialize variables
		 */
		$db			= & $mainframe->getDBO();
		$user		= & $mainframe->getUser();
		$nullDate	= $db->getNullDate();
		$task		= JRequest::getVar('task');

		/*
		 * Create a user access object for the user
		 */
		$access					= new stdClass();
		$access->canEdit		= $user->authorize('action', 'edit', 'content', 'all');
		$access->canEditOwn		= $user->authorize('action', 'edit', 'content', 'own');
		$access->canPublish		= $user->authorize('action', 'publish', 'content', 'all');

		$row = & JTable::getInstance('content', $db);
		if (!$row->bind($_POST))
		{
			JError::raiseError( 500, $row->getError());
		}

		$isNew = ($row->id < 1);
		if ($isNew)
		{
			// new record
			if (!($access->canEdit || $access->canEditOwn))
			{
				JError::raiseError( 403, JText::_("Access Forbidden") );
			}
			$row->created = date('Y-m-d H:i:s');
			$row->created_by = $user->get('id');
		}
		else
		{
			// existing record
			if (!($access->canEdit || ($access->canEditOwn && $row->created_by == $user->get('id'))))
			{
				JError::raiseError( 403, JText::_("Access Forbidden") );
			}
			$row->modified = date('Y-m-d H:i:s');
			$row->modified_by = $user->get('id');
		}
		if (trim($row->publish_down) == 'Never')
		{
			$row->publish_down = $nullDate;
		}

		// code cleaner for xhtml transitional compliance
		$row->introtext = str_replace('<br>', '<br />', $row->introtext);
		$row->fulltext = str_replace('<br>', '<br />', $row->fulltext);

		// remove <br /> take being automatically added to empty fulltext
		$length = strlen($row->fulltext) < 9;
		$search = strstr($row->fulltext, '<br />');
		if ($length && $search)
		{
			$row->fulltext = NULL;
		}

		$row->title = ampReplace($row->title);

		// Publishing state hardening for Authors
		if (!$access->canPublish)
		{
			if ($isNew)
			{
				// For new items - author is not allowed to publish - prevent them from doing so
				$row->state = 0;
			}
			else
			{
				// For existing items keep existing state - author is not allowed to change status
				$query = "SELECT state" .
						"\n FROM #__content" .
						"\n WHERE id = $row->id";
				$db->setQuery($query);
				$state = $db->loadResult();

				if ($state)
				{
					$row->state = 1;
				}
				else
				{
					$row->state = 0;
				}
			}
		}

		if (!$row->check())
		{
			JError::raiseError( 500, $row->getError());
		}
		$row->version++;
		if (!$row->store())
		{
			JError::raiseError( 500, $row->getError());
		}

		// manage frontpage items
		require_once (JApplicationHelper::getPath('class', 'com_frontpage'));
		$fp = new JTableFrontPage($db);

		if (JRequest::getVar('frontpage', false, '', 'boolean'))
		{

			// toggles go to first place
			if (!$fp->load($row->id))
			{
				// new entry
				$query = "INSERT INTO #__content_frontpage" .
						"\n VALUES ( $row->id, 1 )";
				$db->setQuery($query);
				if (!$db->query())
				{
					JError::raiseError( 500, $db->stderror());
				}
				$fp->ordering = 1;
			}
		}
		else
		{
			// no frontpage mask
			if (!$fp->delete($row->id))
			{
				$msg .= $fp->stderr();
			}
			$fp->ordering = 0;
		}
		$fp->reorder();

		$row->checkin();
		$row->reorder("catid = $row->catid");

		// gets section name of item
		$query = "SELECT s.title" .
				"\n FROM #__sections AS s" .
				"\n WHERE s.scope = 'content'" .
				"\n AND s.id = $row->sectionid";
		$db->setQuery($query);
		// gets category name of item
		$section = $db->loadResult();

		$query = "SELECT c.title" .
				"\n FROM #__categories AS c" .
				"\n WHERE c.id = $row->catid";
		$db->setQuery($query);
		$category = $db->loadResult();

		if ($isNew)
		{
			// messaging for new items
			require_once (JApplicationHelper::getPath('class', 'com_messages'));
			$query = "SELECT id" .
					"\n FROM #__users" .
					"\n WHERE sendEmail = 1";
			$db->setQuery($query);
			$users = $db->loadResultArray();
			foreach ($users as $user_id)
			{
				$msg = new mosMessage($db);
				$msg->send($user->get('id'), $user_id, "New Item", sprintf(JText::_('ON_NEW_CONTENT'), $user->get('username'), $row->title, $section, $category));
			}
		}

		$msg = $isNew ? JText::_('THANK_SUB') : JText::_('Item successfully saved.');
		$msg = $user->get('usertype') == 'Publisher' ? JText::_('THANK_SUB') : $msg;
		switch ($task)
		{
			case 'apply' :
				$link = $_SERVER['HTTP_REFERER'];
				break;

			case 'apply_new' :
				$Itemid = JRequest::getVar('Returnid', $Itemid, 'post');
				$link = 'index.php?option=com_content&task=edit&id='.$row->id.'&Itemid='.$Itemid;
				break;

			case 'save' :
			default :
				$Itemid = JRequest::getVar('Returnid', '', 'post');
				if ($Itemid)
				{
					$link = 'index.php?option=com_content&task=view&id='.$row->id.'&Itemid='.$Itemid;
				}
				else
				{
					$link = JRequest::getVar('referer', '', 'post');
				}
				break;
		}
		josRedirect($link, $msg);
	}

	/**
	* Cancels an edit content item operation
	*
	* @static
	* @since 1.0
	*/
	function cancelContent()
	{
		global $mainframe;

		/*
		 * Initialize variables
		 */
		$db			= & $mainframe->getDBO();
		$user		= & $mainframe->getUser();
		$task		= JRequest::getVar('task');
		$Itemid	= JRequest::getVar('Returnid', '0', 'post');
		$referer	= JRequest::getVar('referer', '', 'post');
		$query		= null;

		/*
		 * Create a user access object for the user
		 */
		$access							= new stdClass();
		$access->canEdit			= $user->authorize('action', 'edit', 'content', 'all');
		$access->canEditOwn		= $user->authorize('action', 'edit', 'content', 'own');
		$access->canPublish		= $user->authorize('action', 'publish', 'content', 'all');

		$row = & JTable::getInstance('content', $db);
		$row->bind($_POST);

		if ($access->canEdit || ($access->canEditOwn && $row->created_by == $user->get('id')))
		{
			$row->checkin();
		}

		/*
		 * If the task was edit or cancel, we go back to the content item
		 */
		if ($task == 'edit' || $task == 'cancel')
		{
			$referer = 'index.php?option=com_content&task=view&id='.$row->id.'&Itemid='.$Itemid;
		}

		echo $task;

		/*
		 * If the task was not new, we go back to the referrer
		 */
		if ($referer && $row->id)
		{
			josRedirect($referer);
		}
		else
		{
			josRedirect('index.php');
		}
	}

	/**
	 * Shows the send email form for a content item
	 *
	 * @static
	 * @since 1.0
	 */
	function emailContentForm()
	{
		global $mainframe;

		/*
		 * Initialize variables
		 */
		$db		= & $mainframe->getDBO();
		$user	= & $mainframe->getUser();
		$uid		= JRequest::getVar('id', 0, '', 'int');

		/*
		 * Create a user access object for the user
		 */
		$access							= new stdClass();
		$access->canEdit			= $user->authorize('action', 'edit', 'content', 'all');
		$access->canEditOwn		= $user->authorize('action', 'edit', 'content', 'own');
		$access->canPublish		= $user->authorize('action', 'publish', 'content', 'all');

		$row = & JTable::getInstance('content', $db);
		$row->load($uid);

		if ($row->id === null || $row->access > $user->get('gid'))
		{
			JError::raiseError( 403, JText::_("Access Forbidden") );
		}
		else
		{
			$query = "SELECT template" .
					"\n FROM #__templates_menu" .
					"\n WHERE client_id = 0" .
					"\n AND menuid = 0";
			$db->setQuery($query);
			$template = $db->loadResult();
			JContentViewHTML::emailForm($row->id, $row->title, $template);
		}

	}

	/**
	 * Builds and sends an email to a content item
	 *
	 * @static
	 * @since 1.0
	 */
	function emailContentSend()
	{
		global $mainframe;

		$db				= & $mainframe->getDBO();
		$SiteName	= $mainframe->getCfg('sitename');
		$MailFrom	= $mainframe->getCfg('mailfrom');
		$FromName	= $mainframe->getCfg('fromname');
		$uid				= JRequest::getVar('id', 0, '', 'int');
		$validate		= JRequest::getVar(mosHash('validate'), 0, 'post');

		/*
		 * Create a user access object for the user
		 */
		$access							= new stdClass();
		$access->canEdit			= $user->authorize('action', 'edit', 'content', 'all');
		$access->canEditOwn		= $user->authorize('action', 'edit', 'content', 'own');
		$access->canPublish		= $user->authorize('action', 'publish', 'content', 'all');

		if (!$validate)
		{
			JError::raiseError( 403, JText::_("Access Forbidden") );
		}

		/*
		 * This obviously won't catch all attempts, but it does not hurt to make
		 * sure the request came from a client with a user agent string.
		 */
		if (!isset ($_SERVER['HTTP_USER_AGENT']))
		{
			JError::raiseError( 403, JText::_("Access Forbidden") );
		}

		/*
		 * This obviously won't catch all attempts either, but we ought to check
		 * to make sure that the request was posted as well.
		 */
		if (!$_SERVER['REQUEST_METHOD'] == 'POST')
		{
			JError::raiseError( 403, JText::_("Access Forbidden") );
		}

		// An array of e-mail headers we do not want to allow as input
		$headers = array ('Content-Type:', 'MIME-Version:', 'Content-Transfer-Encoding:', 'bcc:', 'cc:');

		// An array of the input fields to scan for injected headers
		$fields = array ('email', 'yourname', 'youremail', 'subject',);

		/*
		 * Here is the meat and potatoes of the header injection test.  We
		 * iterate over the array of form input and check for header strings.
		 * If we fine one, send an unauthorized header and die.
		 */
		foreach ($fields as $field)
		{
			foreach ($headers as $header)
			{
				if (strpos($_POST[$field], $header) !== false)
				{
					JError::raiseError( 403, JText::_("Access Forbidden") );
				}
			}
		}

		/*
		 * Free up memory
		 */
		unset ($headers, $fields);

		$cache					= & JFactory::getCache('getItemid');
		$_Itemid				= $cache->call( 'JContentHelper::getItemid', $uid);
		$email					= JRequest::getVar('email', '', 'post');
		$yourname			= JRequest::getVar('yourname', '', 'post');
		$youremail			= JRequest::getVar('youremail', '', 'post');
		$subject_default	= sprintf(JText::_('Item sent by'), $yourname);
		$subject				= JRequest::getVar('subject', $subject_default, 'post');

		jimport('joomla.utilities.mail');
		if ($uid < 1 || !$email || !$youremail || (JMailHelper::isEmailAddress($email) == false) || (JMailHelper::isEmailAddress($youremail) == false))
		{
			JContentViewHTML::userInputError(JText::_('EMAIL_ERR_NOINFO'));
		}

		$query = "SELECT template" .
				"\n FROM #__templates_menu" .
				"\n WHERE client_id = 0" .
				"\n AND menuid = 0";
		$db->setQuery($query);
		$template = $db->loadResult();

		/*
		 * Build the link to send in the email
		 */
		$link = sefRelToAbs('index.php?option=com_content&task=view&id='.$uid.'&Itemid='.$_Itemid);

		/*
		 * Build the message to send
		 */
		$msg = sprintf(JText::_('EMAIL_MSG'), $SiteName, $yourname, $youremail, $link);

		/*
		 * Send the email
		 */
		josMail($youremail, $yourname, $email, $subject, $msg);

		JContentViewHTML::emailSent($email, $template);
	}

	function recordVote()
	{
		global $mainframe;

		$db					= & $mainframe->getDBO();
		$url					= JRequest::getVar('url', '');
		$user_rating	= JRequest::getVar('user_rating', 0, '', 'int');
		$cid					= JRequest::getVar('cid', 0, '', 'int');

		/*
		 * Create a user access object for the user
		 */
		$access							= new stdClass();
		$access->canEdit			= $user->authorize('action', 'edit', 'content', 'all');
		$access->canEditOwn		= $user->authorize('action', 'edit', 'content', 'own');
		$access->canPublish		= $user->authorize('action', 'publish', 'content', 'all');

		if (($user_rating >= 1) and ($user_rating <= 5))
		{
			$currip = getenv('REMOTE_ADDR');

			$query = "SELECT *" .
					"\n FROM #__content_rating" .
					"\n WHERE content_id = $cid";
			$db->setQuery($query);
			$votesdb = NULL;
			if (!($db->loadObject($votesdb)))
			{
				$query = "INSERT INTO #__content_rating ( content_id, lastip, rating_sum, rating_count )" .
						"\n VALUES ( $cid, '$currip', $user_rating, 1 )";
				$db->setQuery($query);
				$db->query() or die($db->stderr());
			}
			else
			{
				if ($currip != ($votesdb->lastip))
				{
					$query = "UPDATE #__content_rating" .
							"\n SET rating_count = rating_count + 1, rating_sum = rating_sum + $user_rating, lastip = '$currip'" .
							"\n WHERE content_id = $cid";
					$db->setQuery($query);
					$db->query() or die($db->stderr());
				}
				else
				{
					josRedirect($url, JText::_('You already voted for this poll today!'));
				}
			}
			josRedirect($url, JText::_('Thanks for your vote!'));
		}
	}

	/**
	 * Searches for an item by a key parameter
	 *
	 * @static
	 * @return void
	 * @since 1.0
	 */
	function findKeyItem()
	{
		global $mainframe;

		/*
		 * Initialize variables
		 */
		$db			= & $mainframe->getDBO();
		$user		= & $mainframe->getUser();
		$now		= $mainframe->get('requestTime');
		$keyref	= $db->getEscaped(JRequest::getVar('keyref'));
		$pop		= JRequest::getVar('pop', 0, '', 'int');
		$option	= JRequest::getVar('option');

		$query = "SELECT id" .
				"\n FROM #__content" .
				"\n WHERE attribs LIKE '%keyref=$keyref%'";
		$db->setQuery($query);
		$id = $db->loadResult();
		if ($id > 0)
		{
			showItem($id, $user->get('gid'), $pop, $option, $now);
		}
		else
		{
			JError::raiseError( 404, JText::_("Key Not Found") );
		}
	}
}
?>