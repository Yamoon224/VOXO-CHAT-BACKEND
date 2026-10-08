<?php

namespace Tests\Unit\Architecture;

use App\Domains\Shared\Exceptions\DomainException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Vérifie la forme de SOLID que le cahier des charges exige :
 *
 * - un domaine ne dépend d'un autre que par ses `Contracts` (ou ses modèles
 *   partagés, qui vivent hors des domaines) ;
 * - un contrôleur ne touche jamais Eloquent directement : il délègue à un
 *   service ou à un contrat.
 */
class DomainBoundariesTest extends TestCase
{
    private const APP_PATH = __DIR__.'/../../../app';

    /** @return list<string> */
    private function domains(): array
    {
        $domains = [];

        foreach (glob(self::APP_PATH.'/Domains/*', GLOB_ONLYDIR) as $path) {
            $domains[] = basename($path);
        }

        sort($domains);

        return $domains;
    }

    /** @return iterable<string, array{string}> */
    public static function domainNames(): iterable
    {
        foreach (glob(__DIR__.'/../../../app/Domains/*', GLOB_ONLYDIR) as $path) {
            yield basename($path) => [basename($path)];
        }
    }

    /**
     * Les classes d'un domaine ne référencent, parmi les autres domaines, que
     * du `Contracts` — jamais un `Services`, un `Repositories` ni un `Http`
     * étranger.
     *
     * `Shared` est le socle commun (exception racine, périmètre d'espace de
     * travail, tri, pagination — voir le cahier des charges, section 4.3) :
     * il est fait pour être utilisé directement par tous les domaines, pas
     * seulement via des contrats.
     */
    #[Test]
    #[DataProvider('domainNames')]
    public function un_domaine_ne_depend_d_un_autre_domaine_que_par_ses_contrats(string $domain): void
    {
        $otherDomains = array_values(array_diff($this->domains(), [$domain, 'Shared']));
        $violations = [];

        foreach ($this->phpFiles(self::APP_PATH."/Domains/{$domain}") as $file) {
            $source = $file->getContents();

            foreach ($otherDomains as $other) {
                if (preg_match_all('/App\\\\Domains\\\\'.preg_quote($other, '/').'\\\\([A-Za-z\\\\]+)/', $source, $matches)) {
                    foreach ($matches[1] as $referenced) {
                        if (! str_starts_with($referenced, 'Contracts\\')) {
                            $violations[] = $file->getFilename().' → App\\Domains\\'.$other.'\\'.$referenced;
                        }
                    }
                }
            }
        }

        $this->assertSame([], $violations, "Dépendances hors contrat détectées dans {$domain} :\n".implode("\n", $violations));
    }

    /** Un contrôleur ne construit ni ne questionne un modèle Eloquent directement. */
    #[Test]
    public function aucun_controleur_ne_touche_eloquent_directement(): void
    {
        $violations = [];

        foreach ($this->phpFiles(self::APP_PATH) as $file) {
            if (! str_ends_with($file->getFilename(), 'Controller.php')) {
                continue;
            }

            $class = $this->classNameFromFile($file);
            if ($class === null || ! class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);
            if ($reflection->isAbstract()) {
                continue;
            }

            $source = $file->getContents();

            // Un contrôleur peut recevoir un modèle en paramètre (liaison de
            // route), mais ne doit ni l'interroger (`::query`, `::where`) ni
            // en créer un lui-même.
            if (preg_match('/App\\\\Models\\\\\w+::(query|where|create|find|all)\(/', $source)) {
                $violations[] = $class;
            }
        }

        $this->assertSame([], $violations, "Contrôleurs touchant Eloquent directement :\n".implode("\n", $violations));
    }

    /** Toute exception métier hérite de la racine commune. */
    #[Test]
    public function toute_exception_de_domaine_herite_de_domain_exception(): void
    {
        $violations = [];

        foreach ($this->phpFiles(self::APP_PATH.'/Domains') as $file) {
            if (! str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'Exceptions'.DIRECTORY_SEPARATOR)) {
                continue;
            }

            $class = $this->classNameFromFile($file);
            if ($class === null || ! class_exists($class) || str_ends_with($class, 'DomainException')) {
                continue;
            }

            $reflection = new ReflectionClass($class);
            if (! $reflection->isSubclassOf(DomainException::class)) {
                $violations[] = $class;
            }
        }

        $this->assertSame([], $violations);
    }

    /** @return list<SplFileInfo> */
    private function phpFiles(string $path): array
    {
        if (! is_dir($path)) {
            return [];
        }

        $finder = (new Finder)->files()->in($path)->name('*.php');

        return iterator_to_array($finder, false);
    }

    private function classNameFromFile(SplFileInfo $file): ?string
    {
        $source = $file->getContents();

        if (! preg_match('/namespace\s+([^;]+);/', $source, $namespaceMatch)
            || ! preg_match('/\bclass\s+(\w+)/', $source, $classMatch)) {
            return null;
        }

        return $namespaceMatch[1].'\\'.$classMatch[1];
    }
}
