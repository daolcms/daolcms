# RVE-2022-3 이후 Daol CMS 보안 패치 감사

- 조사 기준일: 2026-08-13
- 조사 대상: Daol CMS `develop` (`db7b0b7f`), `rhymix/rhymix-security`, `YJSoft/xe-core`
- 조사 범위: RVE-2022-4부터 RVE-2026-20까지 32건
- 원격 저장소는 모두 읽기 전용으로 조사했다.

## 결론

| 판정 | 건수 | 의미 |
| --- | ---: | --- |
| 누락 | 24 | Daol에 취약 코드 경로가 존재하고, 핵심 패치가 적용되지 않았다. |
| 부분 적용·추가 검토 | 3 | 일부 방어는 이미 있거나 Rhymix와 코드 경로가 다르지만 추가 보완이 필요하다. |
| 영향 없음 | 5 | 이슈가 명시적으로 XE 1.x를 제외하거나, 취약 기능이 Daol에 없다. |

우선순위는 다음과 같이 표시한다.

- **P0**: RCE, SQL injection, XSS, 권한 우회, 민감정보 노출, 세션 고정, 임의 파일 삭제 등 우선 적용 대상
- **P1**: 관리자 기능과 결합해야 하거나 무결성·경로 검증·방어 심층화 성격이 강한 항목

Rhymix의 현재 코드는 XE 1.x와 차이가 크므로 merge commit을 그대로 cherry-pick하면 안 된다. `YJSoft/xe-core` 커밋이 있는 경우 그 구현을 우선 참고하고, Rhymix merge commit은 보안 요구사항과 누락 파일을 확인하는 용도로 사용하는 것이 안전하다.

## 누락 및 적용 방향

### 2022

| RVE | 판정 | Daol 확인 결과 | 수정 방향 | 참고 커밋 |
| --- | --- | --- | --- | --- |
| [RVE-2022-4](https://github.com/rhymix/rhymix-security/issues/9) 권한 없는 문서 열람·이동 | **선제 보강 완료** | XE 1.x 계열인 Daol은 `module` 요청값과 무관하게 `document_srl`의 실제 모듈을 이미 조회하므로 원 취약점의 영향 범위는 아니다. 다만 요청 모듈이 다르면 조회 결과를 버리고 `mid`로 재조회하던 흐름은 제거했다. | `document_srl`에서 얻은 실제 소속 모듈 정보를 끝까지 유지하여, 공격자가 지정한 `module`·`mid`가 권한 판단의 기준이 되지 않도록 보강했다. | XE 기준 [commit `0918ae8b`](https://github.com/YJSoft/xe-core/commit/0918ae8b7b2d86328c1761d6fc4c1b117762ffea), 후속 [commit `9f5d0977`](https://github.com/YJSoft/xe-core/commit/9f5d097728a56504970a4be6c9efefa1602f831c); Rhymix [merge `83e4b763`](https://github.com/rhymix/rhymix-security/commit/83e4b763ceba820051399491d2706276f2159d51), 핵심 [commit `fa64ad36`](https://github.com/rhymix/rhymix-security/commit/fa64ad360004db15f64452ec795c6c95b4e06e92) |

### 2023

| RVE | 판정 | Daol 확인 결과 | 수정 방향 | 참고 커밋 |
| --- | --- | --- | --- | --- |
| [RVE-2023-1](https://github.com/rhymix/rhymix-security/issues/10) 에디터 reflected XSS | **적용 완료** | 요청의 `component_list`를 무시하고 서버의 컴포넌트 목록으로 항상 덮어쓰도록 수정했다. CKEditor·XpressEditor 출력도 안전하게 인코딩한다. | CKEditor는 컴포넌트 연관배열을 `json_encode()`하고, XpressEditor는 컴포넌트 이름과 제목을 HTML escape하도록 적용했다. | Rhymix [merge `7d148dcf`](https://github.com/rhymix/rhymix-security/commit/7d148dcfc7515d2e24e09218b5588dd2cfc72a21); XE [merge `5cd61c13`](https://github.com/YJSoft/xe-core/commit/5cd61c13c94f51054f7853700c6823dedb814d23), [XpressEditor `220431dd`](https://github.com/YJSoft/xe-core/commit/220431dd81157773eefbe71f20a31dc1576ab3a1) |
| [RVE-2023-2](https://github.com/rhymix/rhymix-security/issues/11) 에디터 미리보기 reflected XSS | **적용 완료** | XpressEditor 호환을 위해 `dispEditorPreview`는 유지하되, 템플릿에서 `$editor_sequence`와 `$content`를 안전하게 처리한다. | sequence에 `intval()`을 적용하고 content는 `removeHackTag()`를 거친 뒤 출력하도록 적용했다. | Rhymix는 별도 보안 merge 없이 기능을 삭제한 [commit `8054564b`](https://github.com/rhymix/rhymix-security/commit/8054564b37548581534a6d95c162ac3dc7ad1dc9); XE 호환 [commit `36057c92`](https://github.com/YJSoft/xe-core/commit/36057c92dcc58eb64dc61a4a53f7037653b2de15) |
| [RVE-2023-3](https://github.com/rhymix/rhymix-security/issues/12) 글 수정 시 작성자 변조 | **적용 완료** | 글·댓글 등록 시 로그인 작성자 정보를 요청값보다 우선하고, 수정 시 기존 작성자 정보를 before 트리거보다 먼저 복원하도록 변경했다. | 회원·익명 작성자의 기존 정보를 유지하되 비회원 글은 닉네임 등 수정을 허용한다. 서드파티의 명시적인 작성자 변경은 before 트리거에서만 가능하도록 트리거 순서를 XE 호환 커밋과 맞췄다. | RVE-2023-4와 함께 Rhymix [merge `71e4118b`](https://github.com/rhymix/rhymix-security/commit/71e4118bd55517f0fd1a51805cecdc341c8175e0); XE [commit `25e2b443`](https://github.com/YJSoft/xe-core/commit/25e2b443985a56e90da8048ecbdf114a87322476) |
| [RVE-2023-4](https://github.com/rhymix/rhymix-security/issues/13) 문서 속성 임의 조작 | **적용 완료** | 게시판·임시저장 HTTP 요청의 `extra_vars`를 제거하고, 비관리자가 문서 날짜와 `list_order`를 바꾸지 못하도록 제한했다. 댓글 식별자는 정수화하고 등록 날짜 입력도 권한별로 제한한다. | XE 호환 구현을 기준으로 적용하고, XE 커밋에서 빠진 비관리자 `list_order` 제거와 임시저장 `extra_vars` 초기화는 Rhymix 병합 구현을 보완 적용했다. | RVE-2023-3과 함께 Rhymix [merge `71e4118b`](https://github.com/rhymix/rhymix-security/commit/71e4118bd55517f0fd1a51805cecdc341c8175e0); XE [commit `25e2b443`](https://github.com/YJSoft/xe-core/commit/25e2b443985a56e90da8048ecbdf114a87322476) |
| [RVE-2023-5](https://github.com/rhymix/rhymix-security/issues/14) 타인의 쪽지 열람 | **적용 완료** | 답장 원문을 표시하기 전에 현재 로그인 회원이 해당 쪽지의 실제 수신자인지 추가로 확인한다. | 기존의 원본 쪽지·발신자 검증에 `source_message->receiver_srl == logged_info->member_srl` 조건을 추가했다. | Rhymix [merge `6f124472`](https://github.com/rhymix/rhymix-security/commit/6f124472bb54657b358b3c66a8bb92fa4222f30d); XE [commit `205b9130`](https://github.com/YJSoft/xe-core/commit/205b9130bbd52c0008d7cc88bdad32a5b96c5528) |
| [RVE-2023-6](https://github.com/rhymix/rhymix-security/issues/15) 타인 게시물에 파일 첨부 | **적용 완료** | 업로드 대상과 모듈을 `editor_sequence` 세션 정보로 고정하고 요청값이 다르면 거부한다. 삭제·대표이미지·파일목록도 실제 파일의 대상과 모듈 관계를 검증한다. | XE 1~3차 호환 수정에 더해 삭제할 파일을 개별 검증한 뒤 유효한 번호만 전달하여 RVE-2026-12 우회까지 함께 차단했다. | Rhymix [merge `f4474070`](https://github.com/rhymix/rhymix-security/commit/f4474070e1ecf2ab9b3b7a88d686d4ce5064a9e6); XE [1차 `7eafde00`](https://github.com/YJSoft/xe-core/commit/7eafde0053563f156f3931b8d35365290614b0d0), [2차 `0a7895b9`](https://github.com/YJSoft/xe-core/commit/0a7895b99d40747613633a67e2a023f5ce1d726e), [3차 `86ec5a9a`](https://github.com/YJSoft/xe-core/commit/86ec5a9adb0b9b0f61cbc6f18a3cb557633380ba) |

### 2024

| RVE | 판정 | Daol 확인 결과 | 수정 방향 | 참고 커밋 |
| --- | --- | --- | --- | --- |
| [RVE-2024-1](https://github.com/rhymix/rhymix-security/issues/16) 자동 생성 제목 XSS | **적용 완료** | 수동 제목과 본문 기반 자동 제목을 저장 전에 escape하고, `DocumentItem::getTitleText()`도 기존 원문 제목을 안전하게 반환하도록 변경했다. | 기본 게시판 스킨의 JavaScript 제목 값은 수작업 문자열 치환 대신 `json_encode()`로 생성한다. | Rhymix [merge `9eea7163`](https://github.com/rhymix/rhymix-security/commit/9eea71631a0b1626f5a74c4e55083d0d875e6657); XE [commit `899d842d`](https://github.com/YJSoft/xe-core/commit/899d842d50f5516b551f5ec2e799f5c8a25cfa31) |
| [RVE-2024-2](https://github.com/rhymix/rhymix-security/issues/17) 외부페이지 RCE 2탄 | **적용 완료** | 외부페이지의 PC·모바일 경로를 저장하기 전에 공통 경로 검사로 검증하고, `files/cache`를 포함한 위험 디렉터리를 거부한다. | RVE-2026-10의 최종 금지 디렉터리 정책과 symlink 실경로 재검증을 적용했다. 실행 시에도 저장된 경로를 다시 검사하여 기존 위험 설정을 즉시 차단한다. | Rhymix [merge `454a8e36`](https://github.com/rhymix/rhymix-security/commit/454a8e36b65fc99e31b49bcc57ebcf69ae6ced43); XE [commit `7a1988a8`](https://github.com/YJSoft/xe-core/commit/7a1988a83873b068d6d114ccdfff2fd436048898) |
| [RVE-2024-3](https://github.com/rhymix/rhymix-security/issues/18) 편집 레이아웃 소스 노출 | **적용 완료** | Apache의 기존 `files/faceOff/*.html` 차단을 유지하고, Nginx도 레이아웃과 생성된 템플릿 소스에 직접 접근하면 403을 반환하도록 변경했다. | Nginx 차단 대상에 `files/(faceOff|ruleset)`을 추가하고 선택적 경로 접두사를 허용해 루트·서브디렉터리 설치에 같은 정책을 적용했다. | Rhymix [merge `670c7b29`](https://github.com/rhymix/rhymix-security/commit/670c7b29fafc1adfc6a5c00750f5b8f9efc6d724); 원래 XE [commit `4e57f849`](https://github.com/xpressengine/xe-core/commit/4e57f84949cc45ca5da9f091961791242954d4f2) |

### 2025

| RVE | 판정 | Daol 확인 결과 | 수정 방향 | 참고 커밋 |
| --- | --- | --- | --- | --- |
| [RVE-2025-1](https://github.com/rhymix/rhymix-security/issues/19) XML 쿼리 정렬 SQL injection | **적용 완료** | 동적 `<index>` 정렬 컬럼과 정렬 방향을 `SortArgument`로 생성하고, `column` 또는 `table.column` 형태의 식별자만 허용한다. | 유효하지 않은 동적 표현식은 XML에 하드코딩된 default로 대체하며, default가 없으면 쿼리 인자를 실패시킨다. XE 커밋의 검증 코드에 더해 Daol `IndexTag`의 정렬 컬럼이 실제로 해당 검증을 사용하도록 연결했다. | Rhymix [merge `5f5cd81f`](https://github.com/rhymix/rhymix-security/commit/5f5cd81f1b06237fbae4e2bdff67c395f2e62a05); XE [commit `ef2d77e6`](https://github.com/YJSoft/xe-core/commit/ef2d77e64717a9ace2617e365bc90218893ffda7) |
| [RVE-2025-2](https://github.com/rhymix/rhymix-security/issues/20) 레이아웃 기능 임의 파일 삭제 | **적용 완료** | 삭제 전에 `layout_srl`의 실제 존재와 요청 파일명이 해당 레이아웃 이미지 목록에 포함되는지 확인한다. `..`, `/`, `\\`, null byte가 포함된 파일명은 거부한다. | 실제 삭제 함수에서도 검증을 반복하고 `realpath()`가 이미지 디렉터리 바로 아래인지 확인한다. symlink와 존재하지 않는 대상은 삭제하지 않는다. | 보안 브랜치 merge 없이 Rhymix 직접 [commit `197295ba`](https://github.com/rhymix/rhymix-security/commit/197295ba437ebb22bab19636ee18dd649cc178e3); XE [commit `086bbf31`](https://github.com/YJSoft/xe-core/commit/086bbf31316236b9fab0fa80712b4fd90532e897) |

### 2026

| RVE | 판정 | Daol 확인 결과 | 수정 방향 | 참고 커밋 |
| --- | --- | --- | --- | --- |
| [RVE-2026-1](https://github.com/rhymix/rhymix-security/issues/21) 파일형 확장변수의 임의 파일 참조 | 영향 없음 | Rhymix 2.1.18에 추가된 파일 업로드형 확장변수 기능이 Daol에 없다. 이슈 본문도 XE는 해당하지 않는다고 명시한다. | 해당 기능을 새로 도입할 때 신규 업로드 파일만 허용하고 `file_srl`과 `upload_target_srl`의 소유 관계를 함께 이식한다. | Rhymix [merge `bcda659a`](https://github.com/rhymix/rhymix-security/commit/bcda659add8739248f04470e9efce720dfbcf179) |
| [RVE-2026-2](https://github.com/rhymix/rhymix-security/issues/22) SVG 업로드 XSS | **누락 P0** | `classes/security/UploadFileFilter.class.php`의 SVG 검사는 단순 `<script`, `<handler>`, `xlink:href` 패턴에 그쳐 namespace prefix, 일반 `href`, `foreignObject` 등을 놓친다. | SVG/XML 위험 패턴 검사를 보강하고, 비관리자 SVG는 검증된 sanitizer를 통과시킨다. SVG 다운로드는 항상 attachment로 강제한다. 라이브러리 도입 시 PHP 버전 호환성과 XXE·외부 URL 차단을 별도 테스트한다. | Rhymix [merge `74b95332`](https://github.com/rhymix/rhymix-security/commit/74b953328158653de2974a8e3ac2b82450e45d3b); XE [commit `d79569ec`](https://github.com/YJSoft/xe-core/commit/d79569ecce6d60ecf6b575b29af0a0a28bc98c16) |
| [RVE-2026-3](https://github.com/rhymix/rhymix-security/issues/23) 애드온 관리 RCE | **누락 P1** | `modules/addon/addon.controller.php`가 `xe_run_method`를 작은따옴표 문자열로 직접 이어 붙여 PHP 캐시를 생성한다. backend allowlist도 없다. | 저장 시 `run_selected`·`no_run_selected`만 허용하고, 캐시 생성에는 `var_export()`를 사용한다. UI select는 보안 경계로 취급하지 않는다. | Rhymix [merge `ea4f116b`](https://github.com/rhymix/rhymix-security/commit/ea4f116b4c8865541af8154574764ef8a828626e), 핵심 `bf089997`; XE [commit `70a279b4`](https://github.com/YJSoft/xe-core/commit/70a279b45852420baae0bea04365395df7934220) |
| [RVE-2026-4](https://github.com/rhymix/rhymix-security/issues/24) 게시판 API 상담글 노출 | **누락 P0** | `ModuleObject`는 view에서 403/404가 발생해도 JSON/XMLRPC API 후처리를 실행할 수 있고, `modules/board/board.api.php`의 존재·접근권한 확인이 약하다. | HTTP 오류 상태에서는 API를 호출하지 않는다. BoardView에서 접근 불가 문서를 빈 객체로 치환하고, BoardAPI도 `isExists()`와 `isAccessible()`을 재검증한다. | Rhymix [merge `f3a08ba8`](https://github.com/rhymix/rhymix-security/commit/f3a08ba8f3d79c91920877868b778be612fc1fc4); XE [commit `b6525daf`](https://github.com/YJSoft/xe-core/commit/b6525dafd6528068b0d6689aa3e8ff6f3e468919) |
| [RVE-2026-5](https://github.com/rhymix/rhymix-security/issues/25) ID/PW 찾기 메일 피싱 | 영향 없음 | 취약 조건인 Rhymix의 “미등록 도메인에서 메인 화면 표시” 설정이 Daol에 없다. Daol은 설정된 `default_url` 중심의 XE 1.x 도메인 모델을 사용한다. | 현재 이슈는 적용하지 않는다. 메일의 절대 URL 생성 시에는 별도로 `Host` 헤더가 아니라 설정된 기본 URL을 사용한다는 회귀 테스트를 둔다. | Rhymix [merge `b7489e6e`](https://github.com/rhymix/rhymix-security/commit/b7489e6e7b9c31d2e1875c392f8ec16c290132a1) |
| [RVE-2026-6](https://github.com/rhymix/rhymix-security/issues/26) ImageMagick command injection | 영향 없음 | Daol 파일 모듈에는 관리자 입력 `magick` 실행 경로나 해당 명령 실행 기능이 없다. | 향후 외부 이미지 변환기를 추가한다면 실행파일 경로를 파일 존재·실행 가능 여부와 단일 경로 형식으로 검증하고 shell 인자와 분리한다. | Rhymix [merge `ae446853`](https://github.com/rhymix/rhymix-security/commit/ae446853067d451cf5d811631ac7946891d687e9) |
| [RVE-2026-7](https://github.com/rhymix/rhymix-security/issues/27) 외부 썸네일 SSRF | **누락 P0** | `DocumentItem`·`CommentItem`이 본문의 임의 `http(s)` 이미지 URL을 `FileHandler::getRemoteFile()`로 요청하며, 이를 끄는 설정이 없다. | 기본값을 “첨부 이미지 전용”으로 바꾸거나 외부 이미지 썸네일 생성을 제거한다. 외부 요청을 유지한다면 DNS 재확인, 사설·루프백·링크로컬 IP 및 redirect 재검증이 필요하다. 임시 디렉터리 접근 차단도 함께 적용한다. XE 참고 커밋은 임시 디렉터리 보호만 하므로 SSRF의 완전한 해결책으로 보지 않는다. | Rhymix [merge `aa0f3f02`](https://github.com/rhymix/rhymix-security/commit/aa0f3f020011aa4ffbba93449243de0159f5fbf4); XE 부분 구현 [commit `03c5ef9d`](https://github.com/YJSoft/xe-core/commit/03c5ef9df4a5dee005eab464b126f39fdc5be3fc) |
| [RVE-2026-8](https://github.com/rhymix/rhymix-security/issues/28) 세션 ID 미갱신 | **누락 P0** | 로그인 성공 경로에 `session_regenerate_id()` 호출이 없다. | 로그인 정보를 세션에 쓰기 직전에 `session_regenerate_id(true)`를 호출한다. XE SSO와는 호환되지 않으므로 SSO 사용 시 명시적으로 비활성화하고 관리자 화면에 경고한다. | Rhymix [merge `a0af01c7`](https://github.com/rhymix/rhymix-security/commit/a0af01c76a0388b8954eb1781f03b6e545accde7); XE [commit `2293e8cc`](https://github.com/YJSoft/xe-core/commit/2293e8cc044d53184449d9954cb84c2aea457825) |
| [RVE-2026-9](https://github.com/rhymix/rhymix-security/issues/29) 자동 링크 XSS | **누락 P0** | `addons/autolink/autolink.js`가 `<`와 `>`만 치환하여 따옴표로 `href` 속성을 탈출할 수 있다. merge에는 레이아웃 소스 미리보기 공격면 제거도 포함되어 있으나 Daol에 남아 있다. | `&`, `<`, `>`, `"`, `'`를 올바르게 escape하거나 DOM API로 링크를 생성한다. 사용하지 않는 `dispLayoutPreview`와 임시 template compile 경로도 제거한다. | Rhymix [merge `877c85ff`](https://github.com/rhymix/rhymix-security/commit/877c85ff2987e514a9fa2262d413f22aeb5c8313); XE [기본 `edfd493f`](https://github.com/YJSoft/xe-core/commit/edfd493f56956fdd8e6fed03d29c744eb3ab9a53), [보충 `d0c3b527`](https://github.com/YJSoft/xe-core/commit/d0c3b52719821faab00a548480f1d5f444e03cef) |
| [RVE-2026-10](https://github.com/rhymix/rhymix-security/issues/30) 외부페이지 RCE 3탄 | **적용 완료 (RVE-2024-2와 함께)** | 공통 외부페이지 경로 검사가 구분자와 null byte를 정규화하고 `files/(attach|cache|config|debug|env|member_extra_info|ruleset|site_design|thumbnails)`를 거부한다. | 저장·실행 경로 모두에서 검사하며, 로컬 경로가 존재하면 `realpath()` 결과도 재귀 검증해 symlink와 경로 이동 우회를 차단한다. | Rhymix [merge `899a1845`](https://github.com/rhymix/rhymix-security/commit/899a18452717c26ac72348a963d4d5ddee02186f); XE [commit `152d3bb3`](https://github.com/YJSoft/xe-core/commit/152d3bb3662e3b9c7d898e15a0cebc5fa210167f) |
| [RVE-2026-11](https://github.com/rhymix/rhymix-security/issues/31) Photoswipe XSS | 영향 없음 | Daol 저장소에 `addons/photoswipe` 및 `rx_photoswipe.js`가 없다. | Photoswipe를 번들 또는 서드파티로 추가할 때 caption은 `innerHTML`이 아니라 `textContent`·`innerText`로 설정한다. | Rhymix [merge `8f5c9473`](https://github.com/rhymix/rhymix-security/commit/8f5c9473592a189c1ee52fe549a2668f04e6293f) |
| [RVE-2026-12](https://github.com/rhymix/rhymix-security/issues/32) 권한 없는 파일 일괄 삭제 | **적용 완료 (RVE-2023-6과 함께)** | `procFileDelete()`가 각 파일의 세션 대상·모듈 소속·삭제 권한을 검증하고 통과한 번호만 별도 배열에 수집한다. | Daol의 `deleteFile()` 입력 형식에 맞춰 유효한 번호만 쉼표 문자열로 변환해 전달하도록 적용했다. | Rhymix [merge `eb925d9d`](https://github.com/rhymix/rhymix-security/commit/eb925d9d2857c1fdc35aa41c407da08960bf9f7a); XE [commit `8357f3f2`](https://github.com/YJSoft/xe-core/commit/8357f3f296186380d84e9bfad6dcc7d4fb26b0bd) |
| [RVE-2026-13](https://github.com/rhymix/rhymix-security/issues/33) 백슬래시 URL open redirect | **누락 P0** | `ModuleHandler::init()`이 `parse_url()` 전에 백슬래시를 정규화하지 않는다. 기존 scheme 검사는 `https:\\evil.example` 우회를 막지 못한다. | URL decode 후 `\\`를 `/`로 정규화하고, host가 있으면 등록 도메인과 정확히 비교한다. host 없이 scheme이 있는 URL은 거부한다. RVE-2026-17의 최종 allowlist와 한 번에 구현한다. | Rhymix [merge `22d51041`](https://github.com/rhymix/rhymix-security/commit/22d5104176ac957ed0b75cc2a262480d3d5b9613); XE [commit `4d2c4b64`](https://github.com/YJSoft/xe-core/commit/4d2c4b647775bf4a4bc21fa11186f05e0de2eae0) |
| [RVE-2026-14](https://github.com/rhymix/rhymix-security/issues/34) importer SSRF·정보유출 | **누락 P1** | `modules/importer/extract.class.php`와 `importer.admin.controller.php`가 외부 HTTP URL을 열고, cache key를 `md5(filename)`으로 예측 가능하게 만든다. | URL 입력 기능을 제거하고 서버에 업로드한 로컬 XML만 허용한다. cache key는 CSPRNG로 생성하고 완료·오류 경로 모두에서 임시 파일을 정리한다. | Rhymix [merge `e13ab220`](https://github.com/rhymix/rhymix-security/commit/e13ab220f993db8a0a37a9203b4d3d20efd2d9ce); XE [commit `fb92cf49`](https://github.com/YJSoft/xe-core/commit/fb92cf49aa17c76906eb5b67bb1e48326d1c1ad2) |
| [RVE-2026-15](https://github.com/rhymix/rhymix-security/issues/35) 애드온 이름 path traversal | **누락 P1** | `selected_addon`, `addon`, `addon_name`이 검증 없이 애드온 XML·디렉터리·캐시 경로에 사용된다. | 모든 진입점에서 애드온 이름을 `^\w+$`로 제한하고 type은 `pc`·`mobile` allowlist로 제한한다. 저장 직전과 조회 직전 모두 검증한다. | Rhymix [merge `9f0823f9`](https://github.com/rhymix/rhymix-security/commit/9f0823f9c81a7de33675b937f40e892709315c92); XE [commit `2d04ba50`](https://github.com/YJSoft/xe-core/commit/2d04ba503f441c3372cfb6f5e7f42dac694b8fa0) |
| [RVE-2026-16](https://github.com/rhymix/rhymix-security/issues/36) 쉬운설치 tar path traversal | **누락 P1** | `modules/autoinstall/autoinstall.lib.php`가 압축 엔트리 이름을 다운로드·FTP·SFTP·직접 설치 경로에 그대로 결합한다. | 공통 `_isSafeArchivePath()`를 두어 null byte, 절대경로, Windows drive·UNC 경로, `..` segment를 거부하고 모든 설치 backend에서 호출한다. | Rhymix는 기존 tar 구현을 별도 merge 없이 제거한 [commit `a044b511`](https://github.com/rhymix/rhymix-security/commit/a044b51152da864b954d1fc1b524956ea1bd9727); XE 직접 패치 [commit `2cbafc41`](https://github.com/YJSoft/xe-core/commit/2cbafc41ac53f9f40913ab1bcf5168a553a76cef) |
| [RVE-2026-17](https://github.com/rhymix/rhymix-security/issues/37) 내부 URL 판별 우회 | **누락 P0** | RVE-2026-13이 아직 없고, 현재 검사는 scheme·host·port를 하나의 엄격한 정책으로 검증하지 않는다. `javascript:`, `data:`, `file:` 및 동일 host의 다른 port를 일관되게 막는 테스트도 없다. | 허용 scheme을 `http`·`https`로 제한한다. 상대 URL만 host 생략을 허용하고, 절대 URL은 IDNA 정규화한 등록 host와 예상 port까지 일치시킨다. URL 검증은 RVE-2026-13·17을 합쳐 단일 helper로 만든다. | Rhymix [merge `cd98ed70`](https://github.com/rhymix/rhymix-security/commit/cd98ed708e06a1d7eb08e938492552ebbe20d748); XE 1차 구현은 RVE-2026-13 [commit `4d2c4b64`](https://github.com/YJSoft/xe-core/commit/4d2c4b647775bf4a4bc21fa11186f05e0de2eae0)이므로 port·IDNA 보완은 Rhymix merge를 추가 참고 |
| [RVE-2026-18](https://github.com/rhymix/rhymix-security/issues/38) DB 오류 시 모듈 권한 우회 | **누락 P0** | `ModuleModel::getGrant()`가 `module.getModuleGrants`의 `toBool()`을 확인하지 않는다. 쿼리 실패와 “설정 없음”을 구분하지 못해 XML 기본 권한으로 fail-open한다. | 설치 완료 상태에서 grant query가 실패하면 예외·오류 화면으로 즉시 중단한다. 실패 결과를 캐시하지 않고, 기본 권한은 성공한 빈 결과에만 적용한다. | Rhymix [merge `de2cb3e5`](https://github.com/rhymix/rhymix-security/commit/de2cb3e5b669986150f95149a7655ee182f50032); 현재 `YJSoft/xe-core`에는 대응 커밋 없음 |
| [RVE-2026-19](https://github.com/rhymix/rhymix-security/issues/39) Referer reflected XSS | **부분 적용·추가 검토 P1** | Rhymix의 `member_auth_referer` helper는 Daol에 없다. Daol 로그인 폼은 `htmlspecialchars($_SERVER['HTTP_REFERER'])`로 escape하여 동일한 reflected XSS 경로는 완화되어 있다. 그러나 회원가입은 raw Referer를 `XE_REDIRECT_URL` 쿠키에 저장한 뒤 redirect에 재사용한다. | 로그인·가입 모두 저장 전에 RVE-2026-17의 내부 HTTP(S) URL helper로 검증하고 escape한다. 쿠키에서 꺼낸 값도 redirect 직전에 재검증하며, 실패하면 설정된 기본 URL로 보낸다. | 기준일 현재 master merge 없음. 브랜치 head [commit `f82e9ae0`](https://github.com/rhymix/rhymix-security/commit/f82e9ae0b4dd7bcb80a41126340ecc122494b040); 현재 `YJSoft/xe-core`에는 대응 커밋 없음 |
| [RVE-2026-20](https://github.com/rhymix/rhymix-security/issues/40) 모듈 관리자 권한 검증 미비 | **부분 적용·추가 검토 P1** | Daol에는 Rhymix의 `manager:config:*` 세분화 권한 모델이 없어 “문서 관리만 허용된 manager”라는 동일 조건은 없다. 다만 `procDocumentAdminInsertExtraVar()`는 `name`, `type`, `eid`를 backend에서 충분히 검증하지 않는다. | 세분화 manager 권한 부분은 적용하지 않는다. 확장변수는 backend에서 `var_idx` 정수화, `name` escape, `eid` 정규식, `type` allowlist를 적용한다. ruleset만 신뢰하지 않는다. | 기준일 현재 master merge 없음. 브랜치 head [commit `0f798c1e`](https://github.com/rhymix/rhymix-security/commit/0f798c1e02e590f7621518999f6366ccd56f24f5); XE의 입력 검증 부분 [commit `a59f1fb7`](https://github.com/YJSoft/xe-core/commit/a59f1fb7ce04eb4a72947d055114eea783a72ffc) |

## 권장 적용 순서

1. 외부페이지 RCE 계열을 RVE-2026-10의 최종 경로 정책으로 한 번에 적용한다. RVE-2024-2는 중간 단계이므로 별도 패치 후 다시 덮어쓰기보다 최종 구현과 회귀 테스트를 함께 넣는 편이 낫다.
2. 파일 계열은 RVE-2023-6을 먼저 적용한 뒤 RVE-2026-12의 다중 삭제 검증을 얹는다. SVG 필터(RVE-2026-2)와 쉬운설치 경로 검증(RVE-2026-16)은 독립 적용할 수 있다.
3. URL 계열은 RVE-2026-13·17·19를 하나의 내부 URL 검증 helper로 묶어 로그인, 가입, `success_return_url`, `error_return_url`에 공통 적용한다.
4. 권한·데이터 노출 계열인 RVE-2023-5, RVE-2026-4, RVE-2026-18을 우선 처리한다. 특히 RVE-2026-18은 DB 오류를 의도적으로 발생시키는 회귀 테스트가 필요하다.
5. XSS 계열 RVE-2023-1·2, RVE-2024-1, RVE-2026-2·9를 처리한 뒤 템플릿과 inline JavaScript에서 수작업 문자열 조립을 추가 검색한다.
6. 나머지 P1 항목과 웹서버 설정(RVE-2024-3)을 적용한다.

## 최소 회귀 테스트 목록

- 에디터: 조작한 `component_list`, `editor_sequence`, 미리보기 content가 script 문맥을 탈출하지 않는지 확인
- 문서·댓글: 수정 요청으로 `member_srl`, 작성자 정보, `list_order`, raw `extra_vars`를 바꿀 수 없는지 확인
- 파일: 다른 글의 `upload_target_srl`, 여러 `file_srl` 혼합 삭제, 대표이미지 교차 지정 차단 확인
- 외부페이지: `files/cache`, `files/attach`, `files/config`, symlink, Windows 구분자, null byte 경로 차단 확인
- URL: 백슬래시 URL, protocol-relative URL, `javascript:`·`data:`·`file:`, IDNA host, 비표준 port, 외부 Referer 차단 확인
- 권한: `module.getModuleGrants` 쿼리가 실패했을 때 비회원·일반회원에게 기본 권한이 부여되지 않는지 확인
- 압축 해제: `../`, `..\\`, 절대경로, drive letter, UNC, null byte 엔트리 차단 확인
- 웹서버: Apache와 Nginx 모두 `files/faceOff/*/layout.html` 직접 요청에 403을 반환하는지 확인

## 조사상 주의사항

- `rhymix-security`의 compare 링크는 보안 브랜치가 master에 merge된 뒤 빈 diff가 될 수 있으므로, 위 표는 first-parent 기준 merge commit을 우선 링크했다.
- RVE-2023-2, RVE-2025-2, RVE-2026-16은 별도의 RVE merge commit이 없어 직접 수정 또는 기능 제거 커밋을 링크했다.
- RVE-2026-19와 RVE-2026-20은 기준일 현재 `rhymix-security/master`에 merge되지 않아 보안 브랜치 head를 링크했다.
- “영향 없음”은 현재 Daol 코드 기준이다. 해당 Rhymix 기능이나 서드파티 애드온을 도입하면 다시 평가해야 한다.
