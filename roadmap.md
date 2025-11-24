# Retrofit PHP - Roadmap

## ✅ Implemented

### Core Features
- [x] HTTP method attributes (`#[GET]`, `#[POST]`, `#[PUT]`, `#[DELETE]`, `#[PATCH]`, `#[HEAD]`, `#[OPTIONS]`)
- [x] Path parameters (`#[Path]`)
- [x] Query parameters (`#[Query]`, `#[QueryMap]`)
- [x] Request body (`#[Body]`)
- [x] Form encoding (`#[FormUrlEncoded]`, `#[Field]`, `#[FieldMap]`)
- [x] Headers (`#[Header]`, `#[HeaderMap]`, `#[Headers]`)
- [x] Multipart attributes (`#[Multipart]`, `#[Part]`, `#[PartMap]`)
- [x] Dynamic URL (`#[Url]`)

### Architecture
- [x] Retrofit builder pattern
- [x] Interface-based API definition
- [x] Dynamic proxy generation
- [x] Converter factory system
- [x] HTTP client abstraction
- [x] Call interface

### HTTP Client
- [x] Guzzle HTTP client adapter
- [x] Sync request execution
- [x] Async request execution (Promises)
- [x] Parallel requests support

### Converters
- [x] JSON request converter
- [x] JSON response converter
- [x] String converter
- [x] Typed JSON converter (DTOs)

### Type Support (Phase 1) ✅
- [x] Custom class deserialization - Map JSON responses to DTO/model classes
- [x] `#[SerializedName]` - Map JSON keys to property names
- [x] `#[ArrayType]` - Typed arrays of objects
- [x] `#[ResponseType]` - Specify response type for array hydration
- [x] Nested object hydration
- [x] Nullable types handling
- [x] DTO serialization for request bodies

### Phase 2: Interceptors ✅
- [x] Request interceptors
- [x] Response interceptors
- [x] Interceptor chain pattern
- [x] `Interceptor` and `Chain` interfaces

### Phase 3: Advanced Features ✅
- [x] Retry policies with backoff (exponential, fixed, linear)
- [x] Per-request timeout configuration (`#[Timeout]` attribute)
- [x] Response caching (in-memory, with TTL)
- [x] Cache policy (configurable, respects Cache-Control headers)
- [x] `#[Cacheable]` attribute for per-method caching
- [x] Pluggable cache backends (`CacheInterface`)
- [x] Request cancellation (already implemented)

### PSR Compliance ✅
- [x] PSR-7 RequestInterface implementation
- [x] PSR-7 ResponseInterface implementation
- [x] Immutable request/response objects

### Testing & Docs
- [x] PHPUnit test suite (192 tests, 405 assertions)
- [x] README documentation (comprehensive)
- [x] Architecture diagrams (Mermaid)
- [x] Example files (async, interceptors, pagination, typed DTOs)
- [x] Roadmap documentation

---

## 📋 Planned

### Phase 4: File Handling
- [ ] Multipart file uploads (streams)
- [ ] Streaming file downloads
- [ ] Progress callbacks

### Phase 5: Error Handling
- [ ] Error body parsing to classes
- [ ] Custom exception types
- [ ] Retry on specific errors

### Phase 6: Alternative Implementations
- [ ] PSR-18 client support
- [ ] XML converter
- [ ] Symfony Serializer integration

---

## 💡 Future Ideas

- PHP Fibers support (coroutines)
- OpenAPI code generation
- Rate limiting
- Circuit breaker pattern
- Metrics/telemetry

---

## Version History

### v1.3.0 (Current)
- **Phase 3: Advanced Features**
- Retry policies with configurable backoff strategies
  - Exponential backoff with jitter support
  - Fixed backoff
  - Linear backoff
- Response caching with TTL
  - In-memory cache implementation
  - Pluggable cache backends (`CacheInterface`)
  - Cache policy with Cache-Control header support
  - `#[Cacheable]` attribute for per-method caching
- Per-request timeout configuration
  - `#[Timeout]` attribute
- PSR-7 compliance
  - Request implements `Psr\Http\Message\RequestInterface`
  - Response implements `Psr\Http\Message\ResponseInterface`
- 192 tests, 405 assertions
- Comprehensive documentation updates

### v1.2.0
- Interceptors support (request/response modification)
- `Interceptor` and `Chain` interfaces
- `InterceptorChain` implementation
- `addInterceptor()` builder method
- 153 tests

### v1.1.0
- DTO/Model class hydration
- `#[SerializedName]` attribute
- `#[ArrayType]` attribute
- `#[ResponseType]` attribute for array hydration
- `TypedJsonConverterFactory`
- 133 tests

### v1.0.0
- Core attribute-based API
- Guzzle HTTP client
- JSON converter
- Async/Promise support
- 105 tests
