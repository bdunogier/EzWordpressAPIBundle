<?php
/**
 * File containing the CategoryService class.
 *
 * @copyright Copyright (C) 2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */
namespace BD\Bundle\EzWordpressAPIBundle\WordpressAPI;

use BD\Bundle\WordpressAPIBundle\Service\CategoryServiceInterface;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Query;

class CategoryService extends BaseService implements CategoryServiceInterface
{
    protected static $blogCategoryIdentifier = 'blog_category';

    public function __construct( array $options = array() )
    {
        if ( isset( $options['blog_category_identifier'] ) )
        {
            self::$blogCategoryIdentifier = $options['blog_category_identifier'];
        }
    }

    public function getList()
    {
        $query = new Query();
        $query->criterion = new Query\Criterion\ContentTypeIdentifier( self::$blogCategoryIdentifier );
        $query->sortClauses = array( new Query\SortClause\ContentName );

        $categories = array();
        foreach ( $this->getRepository()->getSearchService()->findContent( $query )->searchHits as $searchHit )
        {
            $categories[] = $this->serializeCategory( $searchHit->valueObject );
        }

        return $categories;
    }

    /**
     * Returns the categories of a post
     *
     * @param mixed $post
     *
     * @return array Returns the categories of a content
     */
    public function getPostCategories( $postId )
    {
        $contentService = $this->getRepository()->getContentService();
        $locationService = $this->getRepository()->getLocationService();

        $contentInfo = $contentService->loadContentInfo( $postId );
        $locations = $locationService->loadLocations( $contentInfo );

        $categories = array();
        foreach ( $locations as $location )
        {
            $parent = $locationService->loadLocation( $location->parentLocationId );
            if ( $parent->contentInfo->contentTypeId != $this->getCategoryTypeId() )
            {
                continue;
            }
            $categories[] = $this->serializeCategory( $contentService->loadContent( $parent->contentId ) );
        }

        return $categories;
    }

    protected function serializeCategory( Content $category )
    {
        return array(
            'categoryId' => $category->contentInfo->mainLocationId,
            'categoryName' => (string)$category->fields['name']['eng-GB'],
            'categoryDescription' => (string)$category->fields['description']['eng-GB'],
            'description' => (string)$category->fields['description']['eng-GB'],
            'parentId' => $this->getParentCategoryId( $category )
        );
    }

    protected function getParentCategoryId( Content $category )
    {
        $locationService = $this->getRepository()->getLocationService();

        $location = $locationService->loadLocation( $category->contentInfo->mainLocationId );
        $parentLocation = $locationService->loadLocation( $location->parentLocationId );

        if ( $parentLocation->contentInfo->contentTypeId === $this->getCategoryTypeId() )
        {
            return $parentLocation->contentId;
        }

        return 0;
    }

    protected function getCategoryTypeId()
    {
        static $categoryId = null;

        if ( !isset( $categoryId ) )
        {
            $categoryId = $this->getRepository()
                ->getContentTypeService()
                ->loadContentTypeByIdentifier( self::$blogCategoryIdentifier )
                ->id;
        }

        return $categoryId;
    }
}
