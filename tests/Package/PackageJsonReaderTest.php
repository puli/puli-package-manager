<?php

/*
 * This file is part of the puli/repository-manager package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\RepositoryManager\Tests\Package;

use PHPUnit_Framework_TestCase;
use Puli\RepositoryManager\Api\Config\Config;
use Puli\RepositoryManager\Api\Discovery\BindingDescriptor;
use Puli\RepositoryManager\Api\Discovery\BindingParameterDescriptor;
use Puli\RepositoryManager\Api\Discovery\BindingTypeDescriptor;
use Puli\RepositoryManager\Api\Package\InstallInfo;
use Puli\RepositoryManager\Api\Package\PackageFile;
use Puli\RepositoryManager\Api\Repository\ResourceMapping;
use Puli\RepositoryManager\Package\PackageJsonReader;
use Rhumsaa\Uuid\Uuid;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PackageJsonReaderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Config
     */
    private $baseConfig;

    /**
     * @var PackageJsonReader
     */
    private $reader;

    protected function setUp()
    {
        $this->baseConfig = new Config();
        $this->reader = new PackageJsonReader();
    }

    public function testReadFullPackageFile()
    {
        $packageFile = $this->reader->readPackageFile(__DIR__.'/Fixtures/json/full.json');

        $this->assertInstanceOf('Puli\RepositoryManager\Api\Package\PackageFile', $packageFile);
        $this->assertNotInstanceOf('Puli\RepositoryManager\Api\Package\RootPackageFile', $packageFile);
        $this->assertSame(__DIR__.'/Fixtures/json/full.json', $packageFile->getPath());
        $this->assertFullConfig($packageFile);
    }

    public function testReadFullRootPackageFile()
    {
        $packageFile = $this->reader->readRootPackageFile(__DIR__.'/Fixtures/json/full-root.json', $this->baseConfig);

        $installInfo1 = new InstallInfo('vendor/package1', '/path/to/package1');
        $installInfo1->setInstallerName('composer');
        $installInfo1->addEnabledBindingUuid(Uuid::fromString('a54e5668-2b36-43f4-a32c-2d175092b77d'));
        $installInfo1->addDisabledBindingUuid(Uuid::fromString('4d02ee67-d845-4789-a9c1-8301351c6f5a'));
        $installInfo2 = new InstallInfo('vendor/package2', '/path/to/package2');

        $this->assertInstanceOf('Puli\RepositoryManager\Api\Package\RootPackageFile', $packageFile);
        $this->assertSame(__DIR__.'/Fixtures/json/full-root.json', $packageFile->getPath());
        $this->assertFullConfig($packageFile);
        $this->assertSame(array('acme/blog-extension1', 'acme/blog-extension2'), $packageFile->getOverrideOrder());
        $this->assertEquals(array($installInfo1, $installInfo2), $packageFile->getInstallInfos());

        $config = $packageFile->getConfig();
        $this->assertSame('puli-dir', $config->get(Config::PULI_DIR));
        $this->assertSame('Puli\MyFactory', $config->get(Config::FACTORY_CLASS));
        $this->assertSame('puli-dir/MyFactory.php', $config->get(Config::FACTORY_FILE));
        $this->assertSame('my-type', $config->get(Config::REPOSITORY_TYPE));
        $this->assertSame('puli-dir/my-repo', $config->get(Config::REPOSITORY_PATH));
        $this->assertSame('my-store-type', $config->get(Config::DISCOVERY_STORE_TYPE));
    }

    public function testReadMinimalPackageFile()
    {
        $packageFile = $this->reader->readPackageFile(__DIR__.'/Fixtures/json/minimal.json');

        $this->assertInstanceOf('Puli\RepositoryManager\Api\Package\PackageFile', $packageFile);
        $this->assertNotInstanceOf('Puli\RepositoryManager\Api\Package\RootPackageFile', $packageFile);
        $this->assertMinimalConfig($packageFile);
    }

    public function testReadMinimalRootPackageFile()
    {
        $packageFile = $this->reader->readRootPackageFile(__DIR__.'/Fixtures/json/minimal.json', $this->baseConfig);

        $this->assertInstanceOf('Puli\RepositoryManager\Api\Package\RootPackageFile', $packageFile);
        $this->assertMinimalConfig($packageFile);
        $this->assertSame(array(), $packageFile->getOverrideOrder());
    }

    public function testReadBindingTypeWithRequiredParameter()
    {
        $packageFile = $this->reader->readPackageFile(__DIR__.'/Fixtures/json/type-param-required.json', $this->baseConfig);

        $this->assertInstanceOf('Puli\RepositoryManager\Api\Package\PackageFile', $packageFile);
        $this->assertEquals(array(
            new BindingTypeDescriptor('my/type', null, array(
                new BindingParameterDescriptor('param', true),
            ))
        ), $packageFile->getTypeDescriptors());
    }

    public function testReadBindingWithParameters()
    {
        $packageFile = $this->reader->readPackageFile(__DIR__.'/Fixtures/json/binding-params.json', $this->baseConfig);

        $this->assertInstanceOf('Puli\RepositoryManager\Api\Package\PackageFile', $packageFile);
        $this->assertEquals(array(new BindingDescriptor(
            '/app/config*.yml',
            'my/type',
            array('param' => 'value')
        )), $packageFile->getBindingDescriptors());
    }

    public function testReadBindingWithLanguage()
    {
        $packageFile = $this->reader->readPackageFile(__DIR__.'/Fixtures/json/binding-language.json', $this->baseConfig);

        $this->assertInstanceOf('Puli\RepositoryManager\Api\Package\PackageFile', $packageFile);
        $this->assertEquals(array(new BindingDescriptor(
            '//resource[name="config.yml"]',
            'my/type',
            array(),
            'xpath'
        )), $packageFile->getBindingDescriptors());
    }

    public function testRootPackageFileInheritsBaseConfig()
    {
        $packageFile = $this->reader->readRootPackageFile(__DIR__.'/Fixtures/json/minimal.json', $this->baseConfig);

        $this->baseConfig->set(Config::PULI_DIR, 'my-puli-dir');

        $this->assertSame('my-puli-dir', $packageFile->getConfig()->get(Config::PULI_DIR));
    }

    /**
     * @expectedException \Puli\RepositoryManager\Api\InvalidConfigException
     * @expectedExceptionMessage extra-prop.json
     */
    public function testReadPackageFileValidatesSchema()
    {
        $this->reader->readPackageFile(__DIR__.'/Fixtures/json/extra-prop.json');
    }

    /**
     * @expectedException \Puli\RepositoryManager\Api\InvalidConfigException
     * @expectedExceptionMessage extra-prop.json
     */
    public function testReadRootPackageFileValidatesSchema()
    {
        $this->reader->readRootPackageFile(__DIR__.'/Fixtures/json/extra-prop.json', $this->baseConfig);
    }

    /**
     * @expectedException \Puli\RepositoryManager\Api\FileNotFoundException
     * @expectedExceptionMessage bogus.json
     */
    public function testReadPackageFileFailsIfNotFound()
    {
        $this->reader->readPackageFile(__DIR__.'/bogus.json');
    }

    /**
     * @expectedException \Puli\RepositoryManager\Api\FileNotFoundException
     * @expectedExceptionMessage bogus.json
     */
    public function testReadRootPackageFileFailsIfNotFound()
    {
        $this->reader->readRootPackageFile(__DIR__.'/bogus.json', $this->baseConfig);
    }

    /**
     * @expectedException \Puli\RepositoryManager\Api\InvalidConfigException
     * @expectedExceptionMessage win-1258.json
     */
    public function testReadPackageFileFailsIfDecodingNotPossible()
    {
        if (false !== strpos(PHP_VERSION, 'ubuntu')) {
            $this->markTestSkipped('This error is not reported on PHP versions compiled for Ubuntu.');

            return;
        }

        $this->reader->readPackageFile(__DIR__.'/Fixtures/json/win-1258.json');
    }

    /**
     * @expectedException \Puli\RepositoryManager\Api\InvalidConfigException
     * @expectedExceptionMessage win-1258.json
     */
    public function testReadRootPackageFileFailsIfDecodingNotPossible()
    {
        if (false !== strpos(PHP_VERSION, 'ubuntu')) {
            $this->markTestSkipped('This error is not reported on PHP versions compiled for Ubuntu.');

            return;
        }

        $this->reader->readRootPackageFile(__DIR__.'/Fixtures/json/win-1258.json', $this->baseConfig);
    }

    /**
     * @expectedException \Puli\RepositoryManager\Api\Package\UnsupportedVersionException
     * @expectedExceptionMessage lowest readable version
     */
    public function testReadPackageFileVersionTooLow()
    {
        $this->reader->readPackageFile(__DIR__.'/Fixtures/json/version-too-low.json', $this->baseConfig);
    }

    /**
     * @expectedException \Puli\RepositoryManager\Api\Package\UnsupportedVersionException
     * @expectedExceptionMessage highest readable version
     */
    public function testReadPackageFileVersionTooHigh()
    {
        $this->reader->readPackageFile(__DIR__.'/Fixtures/json/version-too-high.json', $this->baseConfig);
    }

    /**
     * @expectedException \Puli\RepositoryManager\Api\Package\UnsupportedVersionException
     * @expectedExceptionMessage lowest readable version
     */
    public function testReadRootPackageFileVersionTooLow()
    {
        $this->reader->readRootPackageFile(__DIR__.'/Fixtures/json/version-too-low.json', $this->baseConfig);
    }

    /**
     * @expectedException \Puli\RepositoryManager\Api\Package\UnsupportedVersionException
     * @expectedExceptionMessage highest readable version
     */
    public function testReadRootPackageFileVersionTooHigh()
    {
        $this->reader->readRootPackageFile(__DIR__.'/Fixtures/json/version-too-high.json', $this->baseConfig);
    }

    ////////////////////////////////////////////////////////////////////////////
    // Test Schema Validation
    ////////////////////////////////////////////////////////////////////////////

    /**
     * @expectedException \Puli\RepositoryManager\Api\InvalidConfigException
     */
    public function testNameMustBeString()
    {
        $this->reader->readPackageFile(__DIR__.'/Fixtures/json/name-not-string.json');
    }

    /**
     * @expectedException \Puli\RepositoryManager\Api\InvalidConfigException
     */
    public function testResourcesMustBeObject()
    {
        $this->reader->readPackageFile(__DIR__.'/Fixtures/json/resources-no-object.json');
    }

    /**
     * @expectedException \Puli\RepositoryManager\Api\InvalidConfigException
     */
    public function testBindingTypesMustBeObject()
    {
        $this->reader->readPackageFile(__DIR__.'/Fixtures/json/type-no-object.json');
    }

    /**
     * @expectedException \Puli\RepositoryManager\Api\InvalidConfigException
     */
    public function testBindingsMustBeObject()
    {
        $this->reader->readPackageFile(__DIR__.'/Fixtures/json/bindings-no-object.json');
    }

    public function testOverrideMayBeArray()
    {
        $packageFile = $this->reader->readPackageFile(__DIR__.'/Fixtures/json/override-array.json');

        $this->assertSame(array('acme/blog-extension1', 'acme/blog-extension2'), $packageFile->getOverriddenPackages());
    }

    /**
     * @expectedException \Puli\RepositoryManager\Api\InvalidConfigException
     */
    public function testOverrideMustBeStringOrArray()
    {
        $this->reader->readPackageFile(__DIR__.'/Fixtures/json/override-no-string.json');
    }

    /**
     * @expectedException \Puli\RepositoryManager\Api\InvalidConfigException
     */
    public function testOverrideEntriesMustBeStrings()
    {
        $this->reader->readPackageFile(__DIR__.'/Fixtures/json/override-entry-no-string.json');
    }

    /**
     * @expectedException \Puli\RepositoryManager\Api\InvalidConfigException
     */
    public function testPackageOrderMustBeArray()
    {
        $this->reader->readPackageFile(__DIR__.'/Fixtures/json/override-order-no-array.json');
    }

    /**
     * @expectedException \Puli\RepositoryManager\Api\InvalidConfigException
     */
    public function testPackageOrderEntriesMustBeStrings()
    {
        $this->reader->readPackageFile(__DIR__.'/Fixtures/json/override-order-entry-no-string.json');
    }

    private function assertFullConfig(PackageFile $packageFile)
    {
        $this->assertSame('my/application', $packageFile->getPackageName());
        $this->assertEquals(array('/app' => new ResourceMapping('/app', array('res'))), $packageFile->getResourceMappings());
        $this->assertEquals(array(new BindingDescriptor('/app/config*.yml', 'my/type')), $packageFile->getBindingDescriptors());
        $this->assertEquals(array(new BindingTypeDescriptor('my/type', 'Description of my type.', array(
            new BindingParameterDescriptor('param', false, 1234, 'Description of the parameter.'),
        ))), $packageFile->getTypeDescriptors());
        $this->assertSame(array('acme/blog'), $packageFile->getOverriddenPackages());
        $this->assertSame(array(
            'extra1' => 'value',
            'extra2' => array('key' => 'value'),
        ), $packageFile->getExtraKeys());
    }

    private function assertMinimalConfig(PackageFile $packageFile)
    {
        $this->assertNull($packageFile->getPackageName());
        $this->assertSame(array(), $packageFile->getResourceMappings());
        $this->assertSame(array(), $packageFile->getBindingDescriptors());
        $this->assertSame(array(), $packageFile->getOverriddenPackages());
    }
}
