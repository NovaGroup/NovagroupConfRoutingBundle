<?

namespace Novagroup\ConfRoutingBundle\Routing;

use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;


class ConfLoader extends FileLoader {
    private $bundle;

    private $placeholders = array();

    /** @var RouteCollection $collection */
    private $collection;


    public function __construct(FileLocatorInterface $locator, $bundle) {
        parent::__construct($locator);

        $this->bundle = $bundle;
    }


    public function load($resource, $type = null) {
        $path = $this->locator->locate($resource);

        $data = file_get_contents($path);

        $data = explode(PHP_EOL, $data);
        $data = array_map('trim', $data);
        $data = array_filter($data, function($line) {
            return !empty($line) && (substr($line, 0, 1) != '#');
        });

        $this->collection = new RouteCollection();

        // Adding the resource will make sure the file gets reloaded when it is changed.
        $this->collection->addResource(new FileResource($path));

        foreach ($data as $line) {
            $line = trim(preg_replace('/#.*$/', '', $line));
            $line = array_filter(preg_split('/[\s]+/', $line));

            switch (count($line)) {
                case 4:
                    $this->parseRoute($line);
                    break;
                case 3:
                    $this->parsePlaceholders($line);
                    break;
                default:
                    throw new \InvalidArgumentException('Invalid rule: ' . implode(' ', $line));
            }
        }

        return $this->collection;
    }


    public function supports($resource, $type = null) {
        return is_string($resource) && (pathinfo($resource, PATHINFO_EXTENSION) === 'conf') && (!$type || ($type === 'conf'));
    }


    private function parsePlaceholders($data) {
        $this->placeholders[$data[0]] = array(
            'requirement' => $data[1],
            'default'     => ($data[2] == '~') ? null : $data[2]
        );
    }


    private function parseRoute($data) {
        $pattern    = $data[0];
        $name       = $data[1];
        $controller = $data[2];

        // Does the controller contain the bundle?
        if (strpos($controller, ':') === false) {
            if (empty($this->bundle)) {
                throw new \InvalidArgumentException(
                    'Bundle missing for: ' . implode(' ', $data) .
                    "\nEither add a bundle to the controller or set the default bundle"
                );
            }

            // Prepend the default bundle.
            $controller = $this->bundle . ':' . $controller;
        }

        $defaults     = array('_controller' => $controller . ':' . $data[3]);
        $requirements = array();

        preg_match_all('/{([a-zA-Z_]+)}/', $pattern, $matches);

        foreach ($matches[1] as $match) {
            if (!isset($this->placeholders[$match])) {
                continue;
            }

            if (!is_null($this->placeholders[$match]['default'])) {
                $defaults[$match] = $this->placeholders[$match]['default'];
            }

          $requirements[] = $this->placeholders[$match]['requirement'];
        }

        $this->collection->add($name, new Route($pattern, $defaults, $requirements));
    }
}
