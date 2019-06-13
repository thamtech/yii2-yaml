<?php
/**
 * @copyright Copyright(c) 2016-2019 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-yaml
 * @license https://opensource.org/licenses/BSD-3-Clause
**/

namespace thamtech\yaml\web;

use thamtech\yaml\helpers\Yaml;
use yii\base\Component;
use yii\web\ResponseFormatterInterface;

/**
 * YamlResponseFormatter formats the given data into Yaml response content.
 *
 * It is used by [[Response]] to format response data.
 *
 * To enable support for Yaml response formatting, configure the application
 * component like the following:
 *
 * ```php
 * 'response' => [
 *     // ...
 *     'formatters' => [
 *         'yaml' => [
 *             'class' => 'thamtech\yaml\web\YamlResponseFormatter',
 *
 *             'inline' => 10, // number of levels before switching to inline YAML
 *             // ...
 *         ]
 *     ],
 * ],
 * ```
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
class YamlResponseFormatter extends Component implements ResponseFormatterInterface
{
    /**
     * @var int The level where the dumper switches to inline YAML
     */
    public $inline = 2;

    /**
     * @var int A bit field of Symfony\Component\Yaml\Yaml::DUMP_*
     *     constants to customize the dumped YAML string
     */
    public $flags = 0;

    /**
     * @var thamtech\yaml\Dumper|array the dumper to use. See [[Yaml::encode()]]
     *     for a description of the $dumper parameter.
     */
    public $dumper = [];

    /**
     * Formats the specified response.
     *
     * @param Response $response the response to be formatted.
     */
    public function format($response)
    {
        $response->getHeaders()->set('Content-Type', 'text/yaml; charset=UTF-8');
        if ($response->data !== null) {
            $response->content = Yaml::encode($response->data, $this->dumper, $this->inline, $this->flags);
        }
    }
}
