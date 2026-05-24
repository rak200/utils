# Reference

Per-class API reference with runnable examples. For installation and a package overview, see the [top-level README](../README.md).

| Class    | Doc                    | What it covers |
| -------- | ---------------------- | -------------- |
| `Str`    | [str.md](str.md)       | Multibyte string helpers |
| `Arr`    | [arr.md](arr.md)       | Array helpers |
| `Num`    | [num.md](num.md)       | Numeric parsing, formatting, aggregation |
| `Rand`   | [rand.md](rand.md)     | CSPRNG, UUID v4/v7, ULID, NanoID |
| `Regex`  | [regex.md](regex.md)   | PCRE wrappers that throw on bad patterns |
| `Hash`   | [hash.md](hash.md)     | Digests, HMAC, password hash/verify |
| `Bit`    | [bit.md](bit.md)       | Bit manipulation on native `int` |
| `File`   | [file.md](file.md)     | Filesystem helpers, line generator |
| `Json`   | [json.md](json.md)     | JSON with implicit `JSON_THROW_ON_ERROR` |
| `Base64` | [base64.md](base64.md) | Standard + URL-safe Base64 |
| `Dt`     | [dt.md](dt.md)         | `DateTimeImmutable` helpers |
| `Url`    | [url.md](url.md)       | URL parse/build, query encode/decode |
| `Path`   | [path.md](path.md)     | Logical path manipulation (no disk access) |

## Conventions used in these docs

- Output is shown in trailing `// …` comments next to each call.
- Time-sensitive helpers (`Rand::uuid`, `Dt::now`, …) get a *shape* example, not a literal value.
- `bare` and `*OrNull` variants are documented together: the bare method throws `RuntimeException`, the `*OrNull` variant returns `?T`.
- All snippets assume the relevant `use Rak200\Utils\X;` import shown at the top of each file.
