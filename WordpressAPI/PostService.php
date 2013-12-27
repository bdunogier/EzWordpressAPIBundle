<?php
/**
 * File containing the PostService class.
 *
 * @copyright Copyright (C) 2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */
namespace BD\Bundle\EzWordpressAPIBundle\WordpressAPI;

use BD\Bundle\WordpressAPIBundle\Service\Post;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Query;

class PostService implements Post
{
    /** @var LocationService */
    protected $locationService;

    /** @var ContentService */
    protected $contentService;

    /** @var ContentTypeService */
    protected $contentTypeService;

    /** @var SearchService */
    protected $searchService;

    protected static $blogPostContentTypeId = 'blog_post';

    public function __construct(
        ContentService $contentService,
        LocationService $locationService,
        ContentTypeService $contentTypeService,
        SearchService $searchService,
        array $options = array()
    )
    {
        $this->contentService = $contentService;
        $this->locationService = $locationService;
        $this->contentTypeService = $contentTypeService;
        $this->searchService = $searchService;

        if ( isset( $options['content_type_id'] ) )
        {
            self::$blogPostContentTypeId = $options['content_type_id'];
        }
    }

    public function createPost( $title, $description, array $categories )
    {
        $createStruct = $this->contentService->newContentCreateStruct(
            $this->contentTypeService->loadContentTypeByIdentifier( self::$blogPostContentTypeId ),
            'eng-GB'
        );
        $createStruct->setField( 'title', $title );

        $draft = $this->contentService->createContent(
            $createStruct,
            array( $this->locationService->newLocationCreateStruct( 2 ) )
        );

        return $this->contentService->publishVersion( $draft->versionInfo )->id;
    }

    public function findRecentPosts( $limit = 5 )
    {
        $query = new Query();
        $query->criterion = new Query\Criterion\ContentTypeIdentifier( 'blog_post' );
        $query->limit = $limit;

        $results = $this->searchService->findContent( $query );
        $recentPosts = array();
        foreach ( $results->searchHits as $searchHit )
        {
            $recentPosts[] = $this->serializeContentAsPost( $searchHit->valueObject );
        }

        return $recentPosts;
    }

    protected function serializeContentAsPost( Content $content )
    {
        return array(
            'post_id' => $content->id,
            'post_title' => (string)$content->fields['title']['eng-GB'],
            'post_date' => $content->versionInfo->creationDate,
            'description' => '',
            'link' => '',
            'userId' => $content->contentInfo->ownerId,
            'dateCreated' => $content->versionInfo->creationDate,
            'date_created_gmt' => $content->versionInfo->creationDate,
            'date_modified' => $content->versionInfo->modificationDate,
            'date_modified_gmt' => $content->versionInfo->modificationDate,
            'wp_post_thumbnail' => 0,
            'categories' => array(),
        );
    }
}
