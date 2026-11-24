#!/usr/bin/env bash
# Roda a suíte em cada versão de PHP suportada, com as dependências resolvidas
# para aquela versão (o que um projeto real nela instalaria), em containers
# oficiais php:X-cli. "7.2-lowest" usa as versões mínimas instaláveis;
# "7.2-guzzle6" força Guzzle 6 (comum em Laravel 5.5–6), que o Composer atual
# bloqueia por alertas de segurança — liberado só na pasta temporária do teste.
#
# Uso: bin/test-matrix.sh [versões...]   (padrão: todas)
# Requer: docker e composer no host (o container php:X-cli não tem unzip).
set -uo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
VERSIONS=("$@")
[ ${#VERSIONS[@]} -eq 0 ] && VERSIONS=(7.2-guzzle6 7.2-lowest 7.2 7.3 7.4 8.0 8.1 8.2 8.3 8.4 8.5)

WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
declare -A RESULT

for entry in "${VERSIONS[@]}"; do
    version="${entry%%-*}"
    dir="$WORK/$entry"
    mkdir -p "$dir"
    cp -r "$ROOT/src" "$ROOT/tests" "$ROOT/config" "$ROOT/composer.json" "$ROOT/phpunit.xml" "$dir/"

    flags=(--no-interaction --quiet)
    [ "$entry" = "$version-lowest" ] && flags+=(--prefer-lowest --prefer-stable)

    echo "== PHP $entry"
    if ! (cd "$dir" && composer config platform.php "$version.99" && composer update "${flags[@]}"); then
        RESULT[$entry]="FALHOU (composer)"
        continue
    fi

    if [ "$entry" = "$version-guzzle6" ] && ! (cd "$dir" && composer config policy.advisories.block false \
        && composer require "guzzlehttp/guzzle:^6.3" -W "${flags[@]}"); then
        RESULT[$entry]="FALHOU (composer guzzle 6)"
        continue
    fi

    (cd "$dir" && composer show 2>/dev/null | grep -E '^(guzzlehttp/guzzle|phpunit/phpunit) ' | awk '{print "   " $1 " " $2}')
    if docker run --rm -v "$dir":/app -w /app "php:$version-cli" vendor/bin/phpunit --colors=never | tail -3; then
        RESULT[$entry]="ok"
    else
        RESULT[$entry]="FALHOU (testes)"
    fi
done

echo
echo "== Resumo"
status=0
for entry in "${VERSIONS[@]}"; do
    printf '  PHP %-11s %s\n' "$entry" "${RESULT[$entry]}"
    [ "${RESULT[$entry]}" = "ok" ] || status=1
done
exit $status
