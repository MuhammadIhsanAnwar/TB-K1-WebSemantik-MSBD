<?php

namespace App\Support;

use RuntimeException;

final class Database3Catalog
{
    private const TYPES = [
        'mv:SitusSejarah' => 'tempat',
        'mv:Museum' => 'tempat',
        'mv:RumahIbadah' => 'tempat',
        'mv:TempatKuliner' => 'tempat',
        'mv:RuangPublik' => 'tempat',
        'mv:PusatBelanja' => 'tempat',
        'mv:RekreasiAir' => 'tempat',
        'mv:Tokoh' => 'tokoh',
        'mv:Kerajaan' => 'kerajaan',
        'mv:Peristiwa' => 'peristiwa',
        'mv:Budaya' => 'budaya',
        'mv:Kuliner' => 'kuliner',
        'mv:ModaTransportasi' => 'transportasi',
        'mv:Kecamatan' => 'wilayah',
    ];

    private const RELATIONS = [
        'mv:dibangunOleh', 'mv:memimpin', 'mv:terlibatDalam', 'mv:merepresentasikan',
        'mv:khasDari', 'mv:dekatDengan', 'mv:bergaya', 'mv:bagianDari',
        'mv:berkaitanDengan', 'mv:memilikiSejarah', 'mv:menjual', 'mv:dilayaniOleh',
        'mv:beradaDi',
    ];

    private static ?array $triples = null;
    private static ?array $entities = null;
    private static ?array $relations = null;

    public static function entities(): array
    {
        if (self::$entities !== null) {
            return self::$entities;
        }

        $bySubject = [];
        foreach (self::triples() as [$subject, $predicate, $object]) {
            $bySubject[$subject][$predicate][] = $object;
        }

        $entities = [];
        foreach ($bySubject as $subject => $properties) {
            if (! str_starts_with($subject, 'res:') || str_starts_with($subject, 'res:relasi-')) {
                continue;
            }

            $type = collect($properties['rdf:type'] ?? [])
                ->map(fn ($value) => self::TYPES[$value] ?? null)
                ->filter()
                ->first();
            if ($type === null) {
                continue;
            }

            $slug = substr($subject, 4);
            $nama = self::first($properties, 'schema:name', self::first($properties, 'rdfs:label', $slug));
            $entity = [
                'slug' => $slug,
                'jenis' => $type,
                'nama' => $nama,
                'ringkas' => self::first($properties, 'schema:description', ''),
            ];

            if ($type === 'tempat') {
                $entity += [
                    'wikidata' => self::wikidata(self::first($properties, 'owl:sameAs', '')),
                    'kategori' => self::category(self::first($properties, 'rdf:type', '')),
                    'kecamatan' => self::kecamatan(self::first($properties, 'schema:containedInPlace', '')),
                    'kelurahan' => self::first($properties, 'mv:kelurahan', ''),
                    'alamat' => self::address($properties, $bySubject),
                    'lat' => (float) self::first($properties, 'geo:lat', 0),
                    'lng' => (float) self::first($properties, 'geo:long', 0),
                    'foto' => self::first($properties, 'schema:image', null),
                    'tiket' => self::offers($properties, $bySubject),
                    'harga_contoh' => self::boolean($properties, 'mv:hargaContoh'),
                    'jam' => self::first($properties, 'mv:keteranganJam', ''),
                    'jam_buka' => self::timeRange($properties),
                    'jam_contoh' => self::boolean($properties, 'mv:jamContoh'),
                    'fasilitas' => $properties['mv:fasilitas'] ?? [],
                    'radius_checkin' => (int) self::first($properties, 'mv:radiusCheckin', 100),
                    'singgah' => (int) self::first($properties, 'mv:durasiSinggah', 45),
                    'cara' => $properties['mv:caraMenuju'] ?? [],
                    'cerita' => [self::first($properties, 'mv:cerita', self::first($properties, 'schema:description', ''))],
                    'sumber' => self::first($properties, 'dcterms:source', null),
                ];
            }

            $entities[$slug] = $entity;
        }

        return self::$entities = $entities;
    }

    public static function relations(): array
    {
        if (self::$relations !== null) {
            return self::$relations;
        }

        $relations = [];
        foreach (self::triples() as [$subject, $predicate, $object]) {
            if (! in_array($predicate, self::RELATIONS, true) || ! str_starts_with($subject, 'res:') || ! str_starts_with($object, 'res:')) {
                continue;
            }

            $relations[] = [
                substr($subject, 4),
                substr($predicate, 4),
                substr($object, 4),
                'sumber' => 'Database 3',
            ];
        }

        return self::$relations = $relations;
    }

    private static function triples(): array
    {
        if (self::$triples !== null) {
            return self::$triples;
        }

        $triples = [];
        for ($offset = 0; ; $offset += 500) {
            $result = SparqlService::jalankan(
                'SELECT ?s ?p ?o WHERE { ?s ?p ?o } ORDER BY ?s ?p ?o OFFSET '.$offset
            );
            if (! ($result['ok'] ?? false)) {
                throw new RuntimeException($result['pesan'] ?? 'Database 3 tidak mengembalikan data RDF.');
            }

            $page = $result['baris'] ?? [];
            $triples = array_merge($triples, $page);
            if (count($page) < 500) {
                break;
            }
        }

        return self::$triples = $triples;
    }

    private static function first(array $properties, string $key, mixed $default): mixed
    {
        return $properties[$key][0] ?? $default;
    }

    private static function boolean(array $properties, string $key): bool
    {
        return filter_var(self::first($properties, $key, false), FILTER_VALIDATE_BOOLEAN);
    }

    private static function wikidata(string $value): ?string
    {
        return str_starts_with($value, 'wd:') ? substr($value, 3) : null;
    }

    private static function kecamatan(string $value): string
    {
        return str_starts_with($value, 'res:kec-') ? ucwords(str_replace('-', ' ', substr($value, 8))) : $value;
    }

    private static function category(string $type): ?string
    {
        return [
            'mv:SitusSejarah' => 'sejarah',
            'mv:Museum' => 'museum',
            'mv:RumahIbadah' => 'religi',
            'mv:TempatKuliner' => 'kuliner',
            'mv:RuangPublik' => 'ruang-publik',
            'mv:PusatBelanja' => 'belanja',
            'mv:RekreasiAir' => 'rekreasi-air',
        ][$type] ?? null;
    }

    private static function address(array $properties, array $bySubject): string
    {
        $address = self::first($properties, 'schema:address', '');

        return is_string($address) ? (string) self::first($bySubject[$address] ?? [], 'schema:streetAddress', '') : '';
    }

    private static function offers(array $properties, array $bySubject): array
    {
        $offers = [];
        foreach ($properties['schema:offers'] ?? [] as $offer) {
            $offerProperties = $bySubject[$offer] ?? [];
            $name = self::first($offerProperties, 'schema:name', null);
            $price = self::first($offerProperties, 'schema:price', null);
            if ($name !== null && $price !== null) {
                $offers[$name] = $price;
            }
        }

        return $offers;
    }

    private static function timeRange(array $properties): ?array
    {
        $open = self::first($properties, 'mv:jamBuka', null);
        $close = self::first($properties, 'mv:jamTutup', null);

        return $open === null || $close === null ? null : [(int) substr((string) $open, 0, 2), (int) substr((string) $close, 0, 2)];
    }
}
