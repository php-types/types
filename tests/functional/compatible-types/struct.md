- `array{name: string}` is a subtype of `array`
- `array{name: string}` is a subtype of `array<array-key, mixed>`
- `array{name: string}` is a subtype of `array<mixed>`
- `array{name: string}` is a subtype of `array{name: string}`

- `array{name: string, age: int}` is a subtype of `array`
- `array{name: string, age: int}` is a subtype of `array<array-key, mixed>`
- `array{name: string, age: int}` is a subtype of `array<mixed>`
- `array{name: string, age: int}` is a subtype of `array{name: string}`
- `array{name: string, age: int}` is a subtype of `array{name: string, age: int}`
- `array{name: string, age: int}` is a subtype of `array{name: string, age?: int}`

- `array{name: string, age?: int}` is a subtype of `array`
- `array{name: string, age?: int}` is a subtype of `array<array-key, mixed>`
- `array{name: string, age?: int}` is a subtype of `array<mixed>`
- `array{name: string, age?: int}` is a subtype of `array{name: string}`
- `array{name: string, age?: int}` is a subtype of `array{name: string, age?: int}`
