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
use eZ\Publish\API\Repository\Values\Content\VersionInfo;

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
        $this->login( 'admin', 'publish' );

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

    public function deletePost( $postId )
    {
        $this->login( 'admin', 'publish' );
        $this->getRepository()->getContentService()->deleteContent(
            $this->getRepository()->getContentService()->loadContentInfo( $postId )
        );
    }

    public function getPost( $postId )
    {
        return $this->serializeContentAsPost(
            $this->getRepository()->getContentService()->loadContent( $postId )
        );
    }

    protected function serializeContentAsPost( Content $content )
    {
        return array(
            'post_id' => $content->id,
            'post_title' => (string)$content->fields['title']['eng-GB'],
            'post_date' => $content->versionInfo->creationDate,
            'post_date_gmt' => $content->versionInfo->creationDate,
            'post_modified' => $content->versionInfo->modificationDate,
            'post_modified_gmt' => $content->versionInfo->modificationDate,
            'post_status' => $this->mapContentStatus( $content->versionInfo->status ),
            'post_format' => 'standard',
            'post_name' => '',
            'post_author' => $content->contentInfo->ownerId,
            'post_password' => '',
            'post_excerpt' => '@todo excerpt',
            'post_content' => '@todo content',
            'post_parent' => 0,
            'post_mime_type' => '',
            'link' => '',
            'guid' => 0,
            'menu_order' => 0,
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'sticky' => false,
            'post_thumbnail' => array( 'thumbnail' => '' ),
            'terms' => array(),
        );
    }

    protected function mapContentStatus( $status )
    {
        switch ( $status )
        {
            case VersionInfo::STATUS_PUBLISHED:
                return 'publish';
            break;

            case VersionInfo::STATUS_DRAFT:
                return 'draft';
            break;
        }
    }
}
