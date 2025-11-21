# Retrofit PHP - Architecture

## Overview

Retrofit PHP turns your interface definitions into HTTP API clients using PHP 8 attributes.

## High-Level Flow

```mermaid
flowchart TB
    A[Define Interface with Attributes] --> B[Create Retrofit Instance]
    B --> C[Call retrofit->create Interface]
    C --> D[Generate Proxy Class]
    D --> E[Call Method on Proxy]
    E --> F[Build HTTP Request]
    F --> G[Execute via Guzzle]
    G --> H[Convert Response]
    H --> I[Return Result]
```

## Detailed Component Flow

### 1. Setup Phase

```mermaid
flowchart LR
    subgraph Builder["RetrofitBuilder"]
        A[baseUrl] --> B[client]
        B --> C[addConverterFactory]
        C --> D[build]
    end
    D --> E[Retrofit Instance]
```

### 2. Service Creation

```mermaid
flowchart TB
    A["retrofit->create(ApiInterface::class)"] --> B{Is Interface?}
    B -->|No| C[Throw Exception]
    B -->|Yes| D[Create ServiceProxy]
    D --> E[Parse Methods via Reflection]
    E --> F[Generate Proxy Class via eval]
    F --> G[Return Proxy Instance]

    subgraph ServiceProxy
        E --> H[ServiceMethod 1]
        E --> I[ServiceMethod 2]
        E --> J[ServiceMethod N]
    end
```

### 3. Method Parsing

```mermaid
flowchart TB
    A[ReflectionMethod] --> B[Parse HTTP Method Attribute]
    A --> C[Parse Parameter Attributes]
    A --> D[Parse Header Attributes]

    subgraph "HTTP Method"
        B --> B1["#[GET]"]
        B --> B2["#[POST]"]
        B --> B3["#[PUT]"]
        B --> B4["#[DELETE]"]
    end

    subgraph "Parameters"
        C --> C1["#[Path]"]
        C --> C2["#[Query]"]
        C --> C3["#[Body]"]
        C --> C4["#[Field]"]
    end

    subgraph "Headers"
        D --> D1["#[Header]"]
        D --> D2["#[Headers]"]
    end
```

### 4. Request Execution

```mermaid
sequenceDiagram
    participant Client as Your Code
    participant Proxy as Generated Proxy
    participant SP as ServiceProxy
    participant SM as ServiceMethod
    participant DC as DefaultCall
    participant Conv as JsonConverter
    participant HTTP as GuzzleHttpClient
    participant API as Remote API

    Client->>Proxy: listRepos("octocat")
    Proxy->>SP: invoke("listRepos", ["octocat"])
    SP->>SM: buildRequest(args)
    SM-->>SP: Request object
    SP->>DC: new DefaultCall(request)
    DC->>Conv: convert(requestBody)
    Conv-->>DC: JSON string
    DC->>HTTP: execute(request)
    HTTP->>API: HTTP GET /users/octocat/repos
    API-->>HTTP: JSON Response
    HTTP-->>DC: Response object
    DC->>Conv: convert(rawBody)
    Conv-->>DC: PHP array
    DC-->>Proxy: Response with body
    Proxy-->>Client: array of repos
```

### 5. Request Building

```mermaid
flowchart TB
    A[Method Call with Args] --> B[ServiceMethod.buildRequest]

    B --> C{For Each Parameter}
    C --> D{Parameter Type?}

    D -->|Path| E["Replace {param} in URL"]
    D -->|Query| F[Add to Query String]
    D -->|Body| G[Set Request Body]
    D -->|Header| H[Add to Headers]
    D -->|Field| I[Add to Form Fields]

    E --> J[Build Final URL]
    F --> J
    G --> K[Set Body]
    H --> L[Set Headers]
    I --> M[Set Form Data]

    J --> N[Create Request Object]
    K --> N
    L --> N
    M --> N
```

### 6. Response Conversion

```mermaid
flowchart TB
    A[Raw HTTP Response] --> B{Has Response Converter?}
    B -->|Yes| C[JsonResponseConverter]
    B -->|No| D[Return Raw Response]

    C --> E[json_decode rawBody]
    E --> F{Valid JSON?}
    F -->|Yes| G[Return PHP Array/Object]
    F -->|No| H[Return null]

    G --> I[Set as Response Body]
    H --> I
    I --> J[Return to Caller]
```

## Class Diagram

```mermaid
classDiagram
    class Retrofit {
        -string baseUrl
        -HttpClient httpClient
        -ConverterFactory[] converterFactories
        +builder() RetrofitBuilder
        +create(interface) T
    }

    class RetrofitBuilder {
        -string baseUrl
        -HttpClient httpClient
        +baseUrl(url) self
        +client(client) self
        +addConverterFactory(factory) self
        +build() Retrofit
    }

    class ServiceProxy {
        -string interface
        -ServiceMethod[] serviceMethods
        +invoke(method, args) Call
    }

    class ServiceMethod {
        -string httpMethod
        -string relativePath
        -ParameterHandler[] parameterHandlers
        +buildRequest(args) Request
    }

    class DefaultCall {
        -HttpClient httpClient
        -Request request
        -Converter requestConverter
        -Converter responseConverter
        +execute() Response
    }

    class HttpClient {
        <<interface>>
        +execute(Request) Response
    }

    class GuzzleHttpClient {
        -ClientInterface client
        +execute(Request) Response
    }

    class ConverterFactory {
        <<interface>>
        +requestBodyConverter() Converter
        +responseBodyConverter() Converter
    }

    class JsonConverterFactory {
        +requestBodyConverter() JsonRequestConverter
        +responseBodyConverter() JsonResponseConverter
    }

    Retrofit --> RetrofitBuilder
    Retrofit --> ServiceProxy
    ServiceProxy --> ServiceMethod
    ServiceProxy --> DefaultCall
    DefaultCall --> HttpClient
    GuzzleHttpClient ..|> HttpClient
    JsonConverterFactory ..|> ConverterFactory
```

## Attribute Processing

```mermaid
flowchart LR
    subgraph "Method Attributes"
        A1["#[GET('/path')]"]
        A2["#[POST('/path')]"]
        A3["#[Headers('...')]"]
        A4["#[FormUrlEncoded]"]
    end

    subgraph "Parameter Attributes"
        B1["#[Path('name')]"]
        B2["#[Query('name')]"]
        B3["#[Body]"]
        B4["#[Header('name')]"]
        B5["#[Field('name')]"]
    end

    A1 --> C[ServiceMethod]
    A2 --> C
    A3 --> C
    A4 --> C

    B1 --> D[ParameterHandler]
    B2 --> D
    B3 --> D
    B4 --> D
    B5 --> D

    C --> E[Request Builder]
    D --> E
```

## File Structure

```
src/
├── Retrofit.php                 # Main entry point
├── RetrofitBuilder.php          # Builder pattern
├── Attributes/
│   ├── Http/                    # HTTP method attributes
│   │   ├── GET.php
│   │   ├── POST.php
│   │   └── ...
│   ├── Parameter/               # Parameter attributes
│   │   ├── Path.php
│   │   ├── Query.php
│   │   ├── Body.php
│   │   └── ...
│   ├── Header.php
│   ├── Headers.php
│   └── FormUrlEncoded.php
├── Contracts/                   # Interfaces
│   ├── HttpClient.php
│   ├── Converter.php
│   ├── ConverterFactory.php
│   └── Call.php
├── Converter/                   # JSON converters
│   ├── JsonConverterFactory.php
│   ├── JsonRequestConverter.php
│   └── JsonResponseConverter.php
├── Http/                        # HTTP layer
│   ├── Request.php
│   ├── Response.php
│   └── GuzzleHttpClient.php
└── Internal/                    # Internal implementation
    ├── ServiceProxy.php
    ├── ServiceMethod.php
    ├── DefaultCall.php
    ├── ParameterHandler.php
    └── ParameterType.php
```
