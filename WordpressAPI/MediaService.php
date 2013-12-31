<?php
/**
 * File containing the MediaService class.
 *
 * @copyright Copyright (C) 2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */
namespace BD\Bundle\EzWordpressAPIBundle\WordpressAPI;

use BD\Bundle\WordpressAPIBundle\Service\MediaServiceInterface;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\SPI\Variation\VariationHandler;
use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Publish\API\Repository\Values\Content\VersionInfo;

class MediaService extends BaseService implements MediaServiceInterface
{
    protected static $imageContentTypeIdentifier = 'image';

    protected static $imageFieldIdentifier = 'image';

    protected static $imageThumbnailVariationName = 'thumbnail';

    /** @var \eZ\Publish\SPI\Variation\VariationHandler */
    protected $imageVariationHandler;

    public function __construct( array $options = array(), VariationHandler $imageVariationHandler )
    {
        if ( isset( $options['image_content_type_identifier'] ) )
        {
            self::$imageContentTypeIdentifier = $options['image_content_type_identifier'];
        }

        if ( isset( $options['image_field_identifier'] ) )
        {
            self::$imageFieldIdentifier = $options['image_field_identifier'];
        }

        if ( isset( $options['image_thumbnail_variation_name'] ) )
        {
            self::$imageThumbnailVariationName = $options['image_thumbnail_variation_name'];
        }

        $this->imageVariationHandler = $imageVariationHandler;
    }

    public function getMedia( $mediaItemId )
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
        $link = '';
        $description = '';

        return array(
            'attachment_id' => $content->id,
            'date_created_gmt' => $content->contentInfo->publishedDate,
            'parent' => $parentId,
            'link' => $link,
            'title' => (string)$content->fields['name']['eng-GB'],
            'caption' => (string)$content->fields['caption']['eng-GB'],
            'description' => $description,
            'thumbnail' => 'http://vm:88/' . $this->getThumbnail( $content ),
            'metadata' => array(
                array(
                    'file' => '',
                    'width' => 0,
                    'height' => 0,
                    'sizes' => array(
                        'thumbnail' => array(
                            'file' => '',
                            'width' => 0,
                            'height' => 0,
                            'mime-type' => ''
                        ),
                        'medium' => array(
                            'file' => '',
                            'width' => 0,
                            'height' => 0,
                            'mime-type' => ''
                        ),
                        'large' => array(
                            'file' => '',
                            'width' => 0,
                            'height' => 0,
                            'mime-type' => ''
                        ),
                        'post-thumbnail' => array(
                            'file' => '',
                            'width' => 0,
                            'height' => 0,
                            'mime-type' => ''
                        ),
                    )
                )
            )
        );
    }

    protected function getThumbnail( Content $content )
    {
        foreach ( $content->getFields() as $field )
        {
            if ( $field->fieldDefIdentifier === self::$imageFieldIdentifier )
            {
                $imageField = $field;
            }
        }

        if ( !isset( $field ) )
        {
            throw new Exception( "Image field not found" );
        }

        return $this->imageVariationHandler->getVariation(
            $imageField, $content->versionInfo, self::$imageThumbnailVariationName
        )->uri;
    }
}
