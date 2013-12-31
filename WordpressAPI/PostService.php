<?php
/**
 * File containing the PostService class.
 *
 * @copyright Copyright (C) 2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */
namespace BD\Bundle\EzWordpressAPIBundle\WordpressAPI;

use BD\Bundle\WordpressAPIBundle\Service\PostServiceInterface;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Query;

class PostService extends BaseService implements PostServiceInterface
{
    protected static $blogPostContentTypeId = 'blog_post';

    public function __construct( array $options = array() )
    {
        if ( isset( $options['content_type_id'] ) )
        {
            self::$blogPostContentTypeId = $options['content_type_id'];
        }
    }

    public function createPost( $title, $description, array $categories )
    {
        $contentService = $this->getRepository()->getContentService();

        $createStruct = $contentService->newContentCreateStruct(
            $this->getRepository()
                ->getContentTypeService()
                ->loadContentTypeByIdentifier( self::$blogPostContentTypeId ),
            'eng-GB'
        );
        $createStruct->setField( 'title', $title );

        $draft = $contentService->createContent(
            $createStruct,
            array( $this->getRepository()->getLocationService()->newLocationCreateStruct( 2 ) )
        );

        return $contentService->publishVersion( $draft->versionInfo )->id;
    }

    public function findRecentPosts( $limit = 5 )
    {
        $query = new Query();
        $query->criterion = new Query\Criterion\ContentTypeIdentifier( 'blog_post' );
        $query->limit = $limit;

        $results = $this->getRepository()->getSearchService()->findContent( $query );
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
