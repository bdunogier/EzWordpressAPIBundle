<?php
/**
 * File containing the MediaService class.
 *
 * @copyright Copyright (C) 2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */
namespace BD\Bundle\EzWordpressAPIBundle\WordpressAPI;

use BD\Bundle\WordpressAPIBundle\Service\Media as MediaServiceInterface;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Query;

class MediaService extends BaseService implements MediaServiceInterface
{
    protected static $imageContentTypeIdentifier = 'image';

    public function __construct( array $options = array() )
    {
        if ( isset( $options['image_content_type_identifier'] ) )
        {
            self::$imageContentTypeIdentifier = $options['image_content_type_identifier'];
        }
    }

    public function getMedia( $id )
    {
    }

    public function getMediaList( $offset = 0, $limit = 50 )
    {
        $this->login( 'admin', 'publish' );

        $query = new Query();
        $query->criterion = new Query\Criterion\ContentTypeIdentifier( self::$imageContentTypeIdentifier );
        $query->offset = $offset;
        $query->limit = $limit;

        $results = $this->getRepository()->getSearchService()->findContent( $query );
        $recentPosts = array();
        foreach ( $results->searchHits as $searchHit )
        {
            $recentPosts[] = $this->serializeContentAsMedia( $searchHit->valueObject );
        }

        return $recentPosts;
    }

    public function uploadFile()
    {
    }

    /**
     * @param Content
     * @return array
     */
    protected function serializeContentAsMedia( Content $content )
    {
        $parentId = 0;
        $thumbnail = '';

        return array(
            'attachment_id' => $content->id,
            'date_created_gmt' => $content->contentInfo->publishedDate,
            'parent' => $parentId,
            'link' => '',
            'title' => (string)$content->fields['name']['eng-GB'],
            'caption' => (string)$content->fields['caption']['eng-GB'],
            'description' => '',
            'thumbnail' => $thumbnail,
            'metadata' => array()
        );
    }
}
