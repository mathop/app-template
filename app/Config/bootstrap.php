<?php
/**
 * This file is loaded automatically by the app/webroot/index.php file after core.php
 *
 * This file should load/create any application wide configuration settings, such as
 * Caching, Logging, loading additional configuration files.
 *
 * You should also use this file to include any files that provide global functions/constants
 * that your application uses.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Config
 * @since         CakePHP(tm) v 0.10.8.2117
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::build(
	[
		'Plugin' => [ROOT . '/Plugin/', ROOT . '/app/Plugin/'],
		'Vendor' => [ROOT . '/vendor/', ROOT . '/app/Vendor/']
	],
	App::RESET
);

/**
 * The settings below can be used to set additional paths to models, views and controllers.
 *
 * App::build(array(
 *     'Model'                     => array('/path/to/models/', '/next/path/to/models/'),
 *     'Model/Behavior'            => array('/path/to/behaviors/', '/next/path/to/behaviors/'),
 *     'Model/Datasource'          => array('/path/to/datasources/', '/next/path/to/datasources/'),
 *     'Model/Datasource/Database' => array('/path/to/databases/', '/next/path/to/database/'),
 *     'Model/Datasource/Session'  => array('/path/to/sessions/', '/next/path/to/sessions/'),
 *     'Controller'                => array('/path/to/controllers/', '/next/path/to/controllers/'),
 *     'Controller/Component'      => array('/path/to/components/', '/next/path/to/components/'),
 *     'Controller/Component/Auth' => array('/path/to/auths/', '/next/path/to/auths/'),
 *     'Controller/Component/Acl'  => array('/path/to/acls/', '/next/path/to/acls/'),
 *     'View'                      => array('/path/to/views/', '/next/path/to/views/'),
 *     'View/Helper'               => array('/path/to/helpers/', '/next/path/to/helpers/'),
 *     'Console'                   => array('/path/to/consoles/', '/next/path/to/consoles/'),
 *     'Console/Command'           => array('/path/to/commands/', '/next/path/to/commands/'),
 *     'Console/Command/Task'      => array('/path/to/tasks/', '/next/path/to/tasks/'),
 *     'Lib'                       => array('/path/to/libs/', '/next/path/to/libs/'),
 *     'Locale'                    => array('/path/to/locales/', '/next/path/to/locales/'),
 *     'Vendor'                    => array('/path/to/vendors/', '/next/path/to/vendors/'),
 *     'Plugin'                    => array('/path/to/plugins/', '/next/path/to/plugins/'),
 * ));
 *
 */

/**
 * Custom Inflector rules, can be set to correctly pluralize or singularize table, model, controller names or whatever other
 * string is passed to the inflection functions
 *
 * Inflector::rules('singular', array('rules' => array(), 'irregular' => array(), 'uninflected' => array()));
 * Inflector::rules('plural', array('rules' => array(), 'irregular' => array(), 'uninflected' => array()));
 *
 */

/**
 * Plugins need to be loaded manually, you can either load them one by one or all of them in a single call
 * Uncomment one of the lines below, as you need. make sure you read the documentation on CakePlugin to use more
 * advanced ways of loading plugins
 *
 * CakePlugin::loadAll(); // Loads all plugins at once
 * CakePlugin::load('DebugKit'); //Loads a single plugin named DebugKit
 *
 */
CakePlugin::load('Crud');

if (php_sapi_name() !== 'cli' && Configure::read('debug') && in_array('DebugKit', App::objects('plugin'))) {
	CakePlugin::load('DebugKit');
	App::uses('CakeEventManager', 'Event');
	CakeEventManager::instance()->attach(function($event) {
		$controller = $event->subject();

		if (!isset($controller->Crud)) {
			return;
		}

		$controller->Toolbar = $controller->Components->load(
			'DebugKit.Toolbar',
			[
				'panels' => [
					'Crud.Crud'
				]
			]
		);
		$controller->Crud->addListener('DebugKit', 'Crud.DebugKit');
	}, 'Controller.initialize');
}

/**
 * You can attach event listeners to the request lifecycle as Dispatcher Filter . By Default CakePHP bundles two filters:
 *
 * - AssetDispatcher filter will serve your asset files (css, images, js, etc) from your themes and plugins
 * - CacheDispatcher filter will read the Cache.check configure variable and try to serve cached content generated from controllers
 *
 * Feel free to remove or add filters as you see fit for your application. A few examples:
 *
 * Configure::write('Dispatcher.filters', array(
 *		'MyCacheFilter', //  will use MyCacheFilter class from the Routing/Filter package in your app.
 *		'MyPlugin.MyFilter', // will use MyFilter class from the Routing/Filter package in MyPlugin plugin.
 * 		array('callable' => $aFunction, 'on' => 'before', 'priority' => 9), // A valid PHP callback type to be called on beforeDispatch
 *		array('callable' => $anotherMethod, 'on' => 'after'), // A valid PHP callback type to be called on afterDispatch
 *
 * ));
 */
Configure::write('Dispatcher.filters', [
	'AssetDispatcher',
	'CacheDispatcher'
]);

/**
 * Configures default file logging options
 */
App::uses('CakeLog', 'Log');
CakeLog::config('debug', [
	'engine' => 'FileLog',
	'types' => ['notice', 'info', 'debug'],
	'path' =>  env('LOG_PATH') ?: LOGS,
	'file' => 'debug',
]);
CakeLog::config('error', [
	'engine' => 'FileLog',
	'types' => ['warning', 'error', 'critical', 'alert', 'emergency'],
	'path' =>  env('LOG_PATH') ?: LOGS,
	'file' => 'error',
]);

$configs = allEnvByPrefix('LOG_URL', 'debug');

if ($configs) {
	$replacements = [
		'/LOGS/' => LOGS
	];
	foreach($configs as $connection => $url) {
		$config = parseEnvUrl($url);
		if (!$config) {
			continue;
		}


		$name = isset($config['name']) ? $config['name'] : strtolower(trim($connection, '_'));
		$engine = isset($config['engine']) ? $config['engine'] : ucfirst(Hash::get($config, 'scheme'));

		$config += array(
			'engine' => $engine,
			'file' => $name
		);

		if (isset($config['types']) && !is_array($config['types'])) {
			$config['types'] = explode(',', $config['types']);
		}

		foreach($config as &$val) {
			$val = str_replace(array_keys($replacements), array_values($replacements), $val);
		}

		debug ($config);

		CakeLog::config($name, $config);
	}
} else {
	$engine = 'File';
	/**
	* Configure the cache used for general framework caching. Path information,
	* object listings, and translation cache files are stored with this configuration.
	*/
	Cache::config('_cake_core_', array(
			'engine' => $engine,
			'prefix' => $prefix . 'cake_core_',
			'path' => CACHE . 'persistent' . DS,
			'serialize' => ($engine === 'File'),
			'duration' => $duration
	));

	/**
	* Configure the cache for model and datasource caches. This cache configuration
	* is used to store schema descriptions, and table listings in connections.
	*/
	Cache::config('_cake_model_', array(
			'engine' => $engine,
			'prefix' => $prefix . 'cake_model_',
			'path' => CACHE . 'models' . DS,
			'serialize' => ($engine === 'File'),
			'duration' => $duration
	));
}
