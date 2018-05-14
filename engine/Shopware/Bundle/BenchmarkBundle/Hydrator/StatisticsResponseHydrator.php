<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\Bundle\BenchmarkBundle\Hydrator;

use Shopware\Bundle\BenchmarkBundle\Exception\StatisticsHydratingException;
use Shopware\Bundle\BenchmarkBundle\Struct\StatisticsResponse;

class StatisticsResponseHydrator implements HydratorInterface
{
    /**
     * @param array $data
     *
     * @throws StatisticsHydratingException
     *
     * @return StatisticsResponse
     */
    public function hydrate(array $data)
    {
        if ($data['message'] !== 'Success') {
            throw new StatisticsHydratingException(sprintf('Expected field "message" to be "success", was "%s"', $data['message']));
        }

        if (empty($data['mappedResponseID'])) {
            throw new StatisticsHydratingException('Missing field "token" from server response');
        }

        return new StatisticsResponse($date = new \DateTime('now'), $data['mappedResponseID']);
    }
}