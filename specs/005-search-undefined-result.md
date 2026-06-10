# Spec 005 — Corrigir "Undefined variable: result" em `search/index`

- **Issue:** [#5 — Error 500 Undefined variable: result](https://github.com/marcelofmatos/phpnetmap/issues/5)
- **Tipo:** Bug
- **Status:** Proposta

## 1. Contexto / requisito

Ao acessar a página de busca (`/search/index`) **sem submeter o formulário**
(carga inicial via GET), a aplicação dispara:

```
[php] Undefined variable: result (protected/views/search/index.php:14)
```

O autor da issue executa o phpnetmap **sem Docker**. Isso é relevante: o
`Dockerfile` define `error_reporting = E_ALL & ~E_NOTICE`, ou seja, **dentro do
container o notice é suprimido** e o bug não aparece. Fora do Docker, com
`error_reporting` padrão, o `E_NOTICE` aparece (e, em configurações que tratam
notice como erro, vira HTTP 500).

## 2. Causa raiz

`protected/controllers/SearchController.php`, `actionIndex()`:

```php
public function actionIndex() {
    $model = new SearchForm;
    $form  = new CForm('application.views.search.form', $model);
    if ($form->submitted('submit') && $model->validate()) {
        $result = $model->searchResult();
        $this->render('index', array('form' => $form, 'searchModel' => $model, 'result' => $result));
    } else {
        $this->render('index', array('form' => $form));   // <-- não passa $result
    }
}
```

No ramo `else` (GET inicial / form não submetido) o view é renderizado **sem**
as variáveis `result` e `searchModel`. O view (`views/search/index.php`) assume
que `$result` sempre existe:

```php
<?php if ($result) : ?>          // linha 14 — $result indefinido no GET
    ...
    switch ($searchModel->type)  // linha 17 — $searchModel também indefinido
```

### Bug latente relacionado

`actionResult()` (linha 52) passa `result` mas **não** passa `searchModel`:

```php
$this->render('index', array('form' => $form, 'result' => $result)); // falta searchModel
```

Se essa rota retornar resultados, o view quebra em `$searchModel->type`
(linha 17). Deve ser corrigido junto.

## 3. Comportamento esperado (spec)

1. `GET /search/index` (sem submeter): renderiza o formulário de busca **sem
   nenhum erro/notice**; a área de resultados simplesmente não aparece.
2. `POST /search/index` com busca válida: renderiza o formulário **e** a grade
   de resultados correspondente ao `type` selecionado.
3. Busca submetida sem resultados: renderiza o formulário sem a área de
   resultados (sem erro).
4. Nenhuma das rotas (`actionIndex`, `actionResult`) pode renderizar o view com
   `result`/`searchModel` indefinidos.

## 4. Design da correção

Defesa em duas camadas (controller garante o contrato; view fica robusto):

**Controller** — sempre fornecer as variáveis que o view consome, em todos os
ramos de `actionIndex()` e `actionResult()`:

- ramo "sem submit": `result => null` (e `searchModel => $model`).
- `actionResult()`: incluir `searchModel => $model`.

**View** (`views/search/index.php`) — tornar o guard tolerante a ausência:

```php
<?php if (!empty($result)) : ?>
```

> Nota: a checagem `$result === null`/`empty` cobre tanto "não submetido"
> quanto "submetido sem resultados". O uso de `$searchModel->type` só ocorre
> dentro do bloco `if (!empty($result))`, então passa a estar sempre definido
> quando alcançado.

Nenhuma mudança de comportamento de busca; apenas inicialização de contrato.

## 5. Critérios de aceite

- [ ] `GET /search/index` retorna HTTP 200 sem `Undefined variable` em log/saída.
- [ ] Com `error_reporting = E_ALL` (sem supressão de notice) a página inicial de
      busca não gera notices.
- [ ] Busca válida exibe a grade de resultados (comportamento atual preservado).
- [ ] Busca sem resultados não gera erro e não exibe a grade.
- [ ] `actionResult()` passa `searchModel` ao view.

## 6. Plano de testes

> ⚠️ **Estado atual da infra de testes:** a suíte em `protected/tests/` é o
> esqueleto padrão do Yii (testa `site/contact`/login via Selenium RC),
> referencia um path inexistente (`yii-1.1.16.bca042`) e o workflow CI
> `tests.yml` está desabilitado (`if: false`) sem `composer.json` na raiz.
> **Ela não roda hoje.** O plano abaixo assume reabilitar um caminho mínimo de
> teste; se não for desejado, validar manualmente pelos critérios de aceite.

**TDD (reproduzir antes de corrigir):**

1. Teste funcional que faz `GET` em `search/index` com `error_reporting`
   incluindo `E_NOTICE` e afirma:
   - resposta sem o texto `Undefined variable`;
   - presença do formulário de busca.
   `phpunit.xml` já tem `convertNoticesToExceptions="true"`, então o notice
   atual faz o teste falhar — exatamente o comportamento a reproduzir.
2. Rodar → falha (bug reproduzido).
3. Aplicar a correção da seção 4.
4. Rodar → passa.

Verificação manual mínima (sem infra de teste):
`php -d error_reporting=E_ALL -S localhost:8000 index-test.php` e acessar
`/search/index`, conferindo ausência de notice no output/log.
