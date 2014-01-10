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
use InvalidArgumentException;

class PostService extends BaseService implements PostServiceInterface
{
    protected static $blogPostContentTypeIdentifier = 'blog_post';

    protected static $imageContentTypeIdentifier = 'blog_image';

    public function __construct( array $options = array() )
    {
        if ( isset( $options['blog_post_content_type_identifier'] ) )
        {
            self::$blogPostContentTypeIdentifier = $options['blog_post_content_type_identifier'];
        }

        if ( isset( $options['image_content_type_identifier'] ) )
        {
            self::$blogPostContentTypeIdentifier = $options['image_content_type_identifier'];
        }
    }

    public function createPost( array $content )
    {
        $contentService = $this->getRepository()->getContentService();

        $createStruct = $contentService->newContentCreateStruct(
            $this->getRepository()
                ->getContentTypeService()
                ->loadContentTypeByIdentifier( self::$blogPostContentTypeIdentifier ),
            'eng-GB'
        );
        $createStruct->setField( 'title', $content['post_title'] );
        $createStruct->setField( 'body', $this->processHtml( $content['post_content'] ) );

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

    public function editPost( $postId, array $content )
    {
        $contentService = $this->getRepository()->getContentService();

        $updateStruct = $contentService->newContentUpdateStruct();
        $updateStruct->setField( 'title', $content['post_title'] );
        $updateStruct->setField( 'body', $this->processHtml( $content['post_content'] ) );
        print_r( $updateStruct );

        $draft = $contentService->updateContent(
            $contentService->createContentDraft( $contentService->loadContentInfo( $postId ) )->versionInfo,
            $updateStruct
        );

        return $contentService->publishVersion( $draft->versionInfo )->id;
    }

    protected function getPostType( $postId )
    {
        $contentType = $this->getRepository()->getContentTypeService()->loadContentType(
            $this->getRepository()->getContentService()->loadContent( $postId )->contentInfo->contentTypeId
        );

        switch ( $contentType->identifier )
        {
            case self::$blogPostContentTypeIdentifier:
                return 'post';
            break;

            case self::$imageContentTypeIdentifier:
                return 'attachment';
            break;

            default:
                throw new InvalidArgumentException( "No post type found for '{$contentType->identifier}'" );
        }
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
            'post_type' => $this->getPostType( $content->id ),
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

    /**
     * Reference XML
     * <?xml version="1.0" encoding="utf-8"?>
     * <section xmlns:image="http://ez.no/namespaces/ezpublish3/image/"
     *          xmlns:xhtml="http://ez.no/namespaces/ezpublish3/xhtml/"
     *          xmlns:custom="http://ez.no/namespaces/ezpublish3/custom/">
     * <paragraph><strong>bold</strong></paragraph>
     * <paragraph><emphasize>italic</emphasize></paragraph>
     * <paragraph><custom name="underline">underline</custom></paragraph>
     * <paragraph><link url_id="59">ez.no</link></paragraph>
     * </section>
     */
    protected function processHtml( $html )
    {
        $html = strip_tags( $html, '<em><strong><u>' );

        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<section xmlns:image="http://ez.no/namespaces/ezpublish3/image/"
         xmlns:xhtml="http://ez.no/namespaces/ezpublish3/xhtml/"
         xmlns:custom="http://ez.no/namespaces/ezpublish3/custom/">
    <paragraph>$html</paragraph>
</section>
XML;
        $xml = str_replace(
            array(
                '<em>',
                '</em>',
                '<u>',
                '</u>',
            ),
            array(
                '<emphasize>',
                '</emphasize>',
                '<custom name="underline">',
                '</custom>'
            ),
            $xml
        );

        return $xml;
    }
}
