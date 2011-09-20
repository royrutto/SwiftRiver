<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Model for Items
 *
 * PHP version 5
 * LICENSE: This source file is subject to GPLv3 license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/gpl.html
 * @author     Ushahidi Team <team@ushahidi.com> 
 * @package    Ushahidi - http://source.swiftly.org
 * @subpackage Models
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License v3 (GPLv3) 
 */
class Model_Item extends ORM
{
	/**
	 * A feed has and belongs to many links, locations, stories and tags
	 *
	 * @var array Relationhips
	 */
	protected $_has_many = array(
		'locations' => array(
			'model' => 'location',
			'through' => 'items_locations'
			),
		'stories' => array(
			'model' => 'story',
			'through' => 'items_stories'
			),
		'tags' => array(
			'model' => 'tag',
			'through' => 'items_tags'
			),
		'links' => array(
			'model' => 'link',
			'through' => 'items_links'
			)			
		);
		
	/**
	 * An item belongs to a project, a feed, a source and a user
	 *
	 * @var array Relationhips
	 */
	protected $_belongs_to = array(
		'project' => array(),
		'feed' => array(),
		'source' => array(),
		'user' => array()
		);

	/**
	 * Overload saving to perform additional functions on the item
	 */
	public function save(Validation $validation = NULL)
	{
		// Ensure Service Goes In as Lower Case
		$this->service = strtolower($this->service);

		// Extract Links
		// Do this for first time items only
		if ($this->loaded() === FALSE)
		{
			$item = parent::save();

			$links = Links::extract($item->item_content);
			foreach ($links as $orig_link)
			{
				$full_link = Links::full($orig_link);
				if ( $orig_link == $full_link OR 
					! $full_link )
				{
					$full_link = $orig_link;
				}

				$link = ORM::factory('link')
					->where('link_full', '=', $full_link)
					->find();

				if ( ! $link->loaded() )
				{
					$link->link = $orig_link;
					$link->link_full = $full_link;
					$link->save();
				}

				if ( ! $item->has('links', $link))
				{
					$item->add('links', $link);
				}
			}
		}
		else
		{
			$item = parent::save();
		}

		// Sweeper Plugin Hook -- save new item
		Event::run('sweeper.save.item', $item);

		return $item;
	}
}