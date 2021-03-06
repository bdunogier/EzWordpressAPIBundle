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
use eZ\Publish\API\Repository\Exceptions\InvalidVariationException;
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
        return $this->serializeContentAsMedia(
            $this->getRepository()->getContentService()->loadContent( $mediaItemId )
        );
    }

    public function getMediaList( $offset = 0, $limit = 50 )
    {
        $query = new Query();
        $query->criterion = new Query\Criterion\ContentTypeIdentifier( self::$imageContentTypeIdentifier );
        $query->offset = $offset;
        $query->limit = $limit;

        $results = $this->getRepository()->getSearchService()->findContent( $query );
        $recentPosts = array();
        foreach ( $results->searchHits as $searchHit )
        {
            try
            {
                $recentPosts[] = $this->serializeContentAsMedia( $searchHit->valueObject );
            }
            catch ( InvalidVariationException $e )
            {
                // @todo log
            }
        }

        return $recentPosts;
    }

    public function createImage( $name, $contents, $type, $overwrite, $contentId = 0 )
    {
        $contentService = $this->getRepository()->getContentService();
        $contentTypeService = $this->getRepository()->getContentTypeService();

        $contentCreateStruct = $contentService->newContentCreateStruct(
            $contentTypeService->loadContentTypeByIdentifier( self::$imageContentTypeIdentifier ),
            'eng-GB'
        );

        $extension = pathinfo( $name, PATHINFO_EXTENSION );
        $temporaryFile = tempnam( sys_get_temp_dir(), 'ezwordpress_' ) . "." . $extension;
        $fp = fopen( $temporaryFile, 'wb' );
        fputs( $fp, $contents );
        fclose( $fp );

        $contentCreateStruct->setField( 'name', $name );
        $contentCreateStruct->setField(
            'image',
            array(
                'id' => $temporaryFile,
                'fileName' => $name,
                'fileSize' => strlen( $contents )
            )
        );

        $content = $contentService->createContent(
            $contentCreateStruct,
            array(
                $this->getRepository()->getLocationService()->newLocationCreateStruct( 51 )
            )
        );

        $content = $contentService->publishVersion( $content->versionInfo );

        $return = array(
            'id' => (string)$content->id,
            'file' => (string)$content->fields['image']['eng-GB'],
            'url' => 'http://vm:88' . (string)$content->fields['image']['eng-GB']->uri,
            'type' => $type
        );

        return $return;
    }

    /**
     * @param Content
     * @return array
     */
    protected function serializeContentAsMedia( Content $content )
    {
        $parentId = 0;
        $description = '';

        return array(
            'attachment_id' => $content->id,
            'date_created_gmt' => $content->contentInfo->publishedDate,
            'parent' => $parentId,
            'link' => 'http://vm:88/' . $this->getVariation( $content, 'original' )->uri,
            'title' => (string)$content->fields['name']['eng-GB'],
            'caption' => (string)$content->fields['caption']['eng-GB'],
            'description' => $description,
            'thumbnail' => 'http://vm:88/' . $this->getThumbnailUri( $content ),
            'metadata' => $this->serializeMetadata( $content )
        );
    }

    protected function serializeMetadata( Content $content )
    {
        $return = $this->serializeImageData( $content, 'full' );
        $return['sizes'] = array(
            'thumbnail' => $this->serializeImageData( $content, 'thumbnail' ),
            'medium' => $this->serializeImageData( $content, 'medium' ),
            'large' => $this->serializeImageData( $content, 'large' ),
            'post-thumbnail' => $this->serializeImageData( $content, 'thumbnail' ),
        );

        return $return;
    }

    protected function serializeImageData( Content $content, $size )
    {
        $variation = $this->getVariation( $content, $size );

        $imageSize = getimagesize( '../../web/' . $variation->uri );

        return array(
            'file' => $variation->fileName,
            'width' => $imageSize[0],
            'height' => $imageSize[1],
            'mime-type' => $variation->mimeType
        );
    }

    protected function getThumbnailUri( Content $content )
    {
        return $this->getVariation( $content, 'thumbnail' )->uri;
    }

    protected function getVariation( $content, $variationName )
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
            $imageField, $content->versionInfo, $variationName
        );
    }
}
