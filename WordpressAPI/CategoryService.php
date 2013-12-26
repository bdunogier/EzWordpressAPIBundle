<?php
/**
 * File containing the CategoryService class.
 *
 * @copyright Copyright (C) 2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */
namespace BD\Bundle\EzWordpressAPIBundle\WordpressAPI;

use BD\Bundle\WordpressAPIBundle\Service\Category;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Query;

class CategoryService implements Category
{
    /** @var SearchService */
    protected $searchService;

    /** @var LocationService */
    protected $locationService;

    public function __construct( SearchService $searchService, LocationService $locationService)
    {
        $this->searchService = $searchService;
        $this->locationService = $locationService;
    }

    public function getList()
    {
        $query = new Query();
        $query->criterion = new Query\Criterion\ContentTypeIdentifier( 'blog_category' );
        $query->sortClauses = array( new Query\SortClause\ContentName );

        $recentPosts = array();
        foreach ( $this->searchService->findContent( $query )->searchHits as $searchHit )
        {
            $recentPosts[] = $this->serializeCategory( $searchHit->valueObject );
        }

        return $recentPosts;
    }

    protected function serializeCategory( Content $category )
    {
        $location = $this->locationService->loadLocation( $category->contentInfo->mainLocationId );

        return array(
            'categoryId' => $category->contentInfo->mainLocationId,
            'categoryName' => (string)$category->fields['name']['eng-GB'],
            'categoryDescription' => (string)$category->fields['description']['eng-GB'],
            'description' => (string)$category->fields['description']['eng-GB'],
            'parentId' => $location->parentLocationId
        );
    }
}
