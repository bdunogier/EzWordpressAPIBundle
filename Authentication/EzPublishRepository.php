<?php
/**
 * File containing the EzPublishRepository WordpressAPI authentication handler class.
 *
 * @copyright Copyright (C) 2014 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */
namespace BD\Bundle\EzWordpressAPIBundle\Authentication;

use BD\Bundle\WordpressAPIBundle\Authentication\AuthenticationHandlerInterface;
use eZ\Publish\API\Repository\Repository;

class EzPublishRepository implements AuthenticationHandlerInterface
{
    /** @var Repository */
    protected $repository;

    public function __construct( Repository $repository )
    {
        $this->repository = $repository;
    }

    public function login( $username, $password )
    {
        $this->repository->setCurrentUser(
            $this->repository->getUserService()->loadUserByCredentials( $username, $password )
        );
    }
}
