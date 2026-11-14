package vn.hosoweb.service;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;

import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.time.Duration;
import java.util.*;

/**
 * Gửi URL tới Sinbyte để lập chỉ mục.
 * <p>Cấu hình bằng biến môi trường:
 * <ul>
 *   <li>{@code SINBYTE_API_KEY} — khóa bí mật trên máy chủ (dùng khi ô khóa trên form để trống)</li>
 *   <li>{@code SINBYTE_API_URL} — endpoint, mặc định {@value #DEFAULT_URL}</li>
 *   <li>{@code SINBYTE_MOCK=true} — trả về phản hồi mô phỏng, không gọi mạng</li>
 * </ul>
 */
public class SinbyteClient {
    public static final String DEFAULT_URL = "https://app.sinbyte.com/api/indexing/";
    public static final int MAX_URLS = 100;

    public record Response(boolean accepted, String taskId, String message, int httpStatus) { }
    /** Chi tiết một tác vụ: trạng thái tác vụ và URL → đã index hay chưa. */
    public record TaskDetail(boolean ok, String status, Map<String, Boolean> indexed, String message) { }

    private static final ObjectMapper JSON = new ObjectMapper();

    public static boolean hasServerKey() {
        String k = System.getenv("SINBYTE_API_KEY");
        return k != null && !k.isBlank();
    }

    public static boolean isMock() {
        return "true".equalsIgnoreCase(System.getenv("SINBYTE_MOCK"));
    }

    public Response submit(String overrideKey, String campaignName, List<String> urls) {
        if (urls == null || urls.isEmpty()) return new Response(false, null, "Không có URL để gửi", 0);
        if (urls.size() > MAX_URLS) return new Response(false, null, "Tối đa " + MAX_URLS + " URL mỗi lượt", 0);
        String key = overrideKey != null && !overrideKey.isBlank() ? overrideKey.trim() : System.getenv("SINBYTE_API_KEY");
        if (isMock()) {
            return new Response(true, "mock-" + UUID.randomUUID().toString().substring(0, 8),
                    "Phản hồi mô phỏng (SINBYTE_MOCK=true)", 200);
        }
        if (key == null || key.isBlank()) {
            return new Response(false, null, "Chưa cấu hình khóa Sinbyte trên máy chủ (SINBYTE_API_KEY)", 0);
        }
        String endpoint = Optional.ofNullable(System.getenv("SINBYTE_API_URL")).filter(s -> !s.isBlank()).orElse(DEFAULT_URL);
        try {
            Map<String, Object> body = new LinkedHashMap<>();
            body.put("apikey", key);
            body.put("name", campaignName);
            body.put("dripfeed", 1);
            body.put("urls", urls);
            HttpRequest req = HttpRequest.newBuilder(URI.create(endpoint))
                    .timeout(Duration.ofSeconds(30))
                    .header("Content-Type", "application/json")
                    .header("Accept", "application/json")
                    .POST(HttpRequest.BodyPublishers.ofString(JSON.writeValueAsString(body)))
                    .build();
            HttpResponse<String> res = HttpClient.newBuilder().connectTimeout(Duration.ofSeconds(15)).build()
                    .send(req, HttpResponse.BodyHandlers.ofString());
            return interpret(res.statusCode(), res.body());
        } catch (Exception e) {
            return new Response(false, null, "Lỗi kết nối Sinbyte: " + e.getMessage(), 0);
        }
    }

    /** Đọc chi tiết tác vụ (GET, không tốn credit). */
    public TaskDetail detail(String overrideKey, String taskId) {
        String key = overrideKey != null && !overrideKey.isBlank() ? overrideKey.trim() : System.getenv("SINBYTE_API_KEY");
        if (isMock()) return new TaskDetail(true, "mock", Map.of(), "Mô phỏng");
        if (key == null || key.isBlank()) return new TaskDetail(false, null, Map.of(), "Chưa cấu hình khóa Sinbyte");
        if (taskId == null || !taskId.matches("\\d{1,15}")) return new TaskDetail(false, null, Map.of(), "Mã tác vụ không hợp lệ");
        String endpoint = Optional.ofNullable(System.getenv("SINBYTE_API_URL")).filter(s -> !s.isBlank()).orElse(DEFAULT_URL);
        if (!endpoint.endsWith("/")) endpoint += "/";
        try {
            URI uri = URI.create(endpoint + taskId + "/?apikey=" + java.net.URLEncoder.encode(key, java.nio.charset.StandardCharsets.UTF_8));
            HttpRequest req = HttpRequest.newBuilder(uri).timeout(Duration.ofSeconds(30))
                    .header("Accept", "application/json").GET().build();
            HttpResponse<String> res = HttpClient.newBuilder().connectTimeout(Duration.ofSeconds(15)).build()
                    .send(req, HttpResponse.BodyHandlers.ofString());
            return parseDetail(res.statusCode(), res.body());
        } catch (Exception e) {
            return new TaskDetail(false, null, Map.of(), "Lỗi kết nối Sinbyte: " + e.getMessage());
        }
    }

    static TaskDetail parseDetail(int status, String body) {
        try {
            JsonNode n = JSON.readTree(body == null ? "" : body);
            if (status < 200 || status >= 300 || n == null || !n.isObject()) {
                String m = n != null && n.isObject() ? firstText(n, "message", "detail", "error") : null;
                return new TaskDetail(false, null, Map.of(), m != null ? m : "HTTP " + status);
            }
            Map<String, Boolean> map = new LinkedHashMap<>();
            JsonNode urls = n.get("urls");
            if (urls != null && urls.isArray()) {
                for (JsonNode u : urls) {
                    String url = u.path("url").asText("");
                    if (url.isEmpty()) continue;
                    String ix = u.path("indexed").asText("");
                    map.put(url, ix.equalsIgnoreCase("yes") || ix.equalsIgnoreCase("true") || ix.equals("1"));
                }
            }
            return new TaskDetail(true, firstText(n, "status"), map, null);
        } catch (Exception e) {
            return new TaskDetail(false, null, Map.of(), "Phản hồi Sinbyte không đọc được");
        }
    }

    /** Diễn giải phản hồi — tách riêng để kiểm thử. */
    static Response interpret(int status, String body) {
        String taskId = null, message = null;
        boolean okFlag = status >= 200 && status < 300;
        try {
            JsonNode n = JSON.readTree(body == null ? "" : body);
            if (n != null && n.isObject()) {
                taskId = firstText(n, "task_id", "taskId", "taskid", "id");
                JsonNode data = n.get("data");
                if (taskId == null && data != null && data.isObject()) taskId = firstText(data, "task_id", "taskId", "taskid", "id");
                message = firstText(n, "message", "msg", "error");
                String st = firstText(n, "status", "result");
                if (st != null && (st.equalsIgnoreCase("error") || st.equalsIgnoreCase("fail") || st.equalsIgnoreCase("failed")
                        || st.equalsIgnoreCase("false"))) okFlag = false;
                JsonNode success = n.get("success");
                if (success != null && success.isBoolean() && !success.asBoolean()) okFlag = false;
            }
        } catch (Exception ignored) {
            message = body == null ? null : body.substring(0, Math.min(200, body.length()));
        }
        if (message == null) message = okFlag ? "Sinbyte đã tiếp nhận" : "HTTP " + status;
        return new Response(okFlag, taskId, message, status);
    }

    private static String firstText(JsonNode n, String... keys) {
        for (String k : keys) {
            JsonNode v = n.get(k);
            if (v != null && !v.isNull() && !v.isContainerNode() && !v.asText().isBlank()) return v.asText();
        }
        return null;
    }
}
