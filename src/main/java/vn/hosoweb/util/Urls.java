package vn.hosoweb.util;

import java.net.URI;
import java.util.Locale;

public final class Urls {
    private Urls() { }

    /** Chỉ chấp nhận URL tuyệt đối http/https có host. */
    public static boolean isHttpUrl(String s) {
        if (s == null || s.isBlank()) return false;
        try {
            URI u = new URI(s.trim());
            String sc = u.getScheme();
            return sc != null && (sc.equalsIgnoreCase("http") || sc.equalsIgnoreCase("https"))
                    && u.getHost() != null && !u.getHost().isBlank();
        } catch (Exception e) {
            return false;
        }
    }

    /** Thêm https:// nếu người dùng nhập dạng "example.com/path". */
    public static String normalize(String s) {
        if (s == null) return null;
        String t = s.trim();
        if (t.isEmpty()) return null;
        if (!t.matches("(?i)^[a-z][a-z0-9+.-]*://.*") && t.matches("(?i)^(www\\.)?[a-z0-9-]+(\\.[a-z0-9-]+)+([/?#].*)?$")) {
            t = "https://" + t;
        }
        return t;
    }

    public static String host(String s) {
        try {
            String h = new URI(s.trim()).getHost();
            if (h == null) return null;
            h = h.toLowerCase(Locale.ROOT);
            return h.startsWith("www.") ? h.substring(4) : h;
        } catch (Exception e) {
            return null;
        }
    }

    /** Khóa so sánh để tránh trùng: host + path, bỏ dấu / cuối và tham số. */
    public static String dedupeKey(String s) {
        if (s == null) return null;
        try {
            URI u = new URI(s.trim());
            String h = host(s);
            String p = u.getPath() == null ? "" : u.getPath().replaceAll("/+$", "");
            return (h == null ? s.trim().toLowerCase(Locale.ROOT) : h + p.toLowerCase(Locale.ROOT));
        } catch (Exception e) {
            return s.trim().toLowerCase(Locale.ROOT);
        }
    }
}
