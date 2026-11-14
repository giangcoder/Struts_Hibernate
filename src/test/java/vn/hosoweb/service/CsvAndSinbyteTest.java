package vn.hosoweb.service;

import org.junit.jupiter.api.Test;

import java.util.Arrays;

import static org.junit.jupiter.api.Assertions.*;

class CsvAndSinbyteTest {
    @Test
    void csvEscapesAndBlocksFormulas() {
        assertEquals("a,\"b,c\",\"q\"\"x\",'=SUM(A1),\r\n", Csv.row(Arrays.asList("a", "b,c", "q\"x", "=SUM(A1)", null)));
    }

    @Test
    void sinbyteInterpretsResponses() {
        var ok = SinbyteClient.interpret(200, "{\"status\":\"ok\",\"task_id\":\"T123\"}");
        assertTrue(ok.accepted());
        assertEquals("T123", ok.taskId());

        var nested = SinbyteClient.interpret(200, "{\"data\":{\"id\":42}}");
        assertTrue(nested.accepted());
        assertEquals("42", nested.taskId());

        var err = SinbyteClient.interpret(200, "{\"status\":\"error\",\"message\":\"Hết credit\"}");
        assertFalse(err.accepted());
        assertEquals("Hết credit", err.message());

        assertFalse(SinbyteClient.interpret(401, "Unauthorized").accepted());
        // Định dạng thật khi khóa sai
        var bad = SinbyteClient.interpret(400, "{\"message\":\"Invalid apikey\"}");
        assertFalse(bad.accepted());
        assertEquals("Invalid apikey", bad.message());
        // Định dạng thật của tác vụ vừa tạo / chi tiết
        var created = SinbyteClient.interpret(201, "{\"id\":7840887,\"name\":\"x\",\"status\":\"pending\",\"total\":2}");
        assertTrue(created.accepted());
        assertEquals("7840887", created.taskId());
    }

    @Test
    void sinbyteParsesTaskDetail() {
        var d = SinbyteClient.parseDetail(200, "{\"id\":7828057,\"status\":\"done\",\"urls\":[{\"id\":1,\"url\":\"https://a.com/x\",\"indexed\":\"yes\"},{\"id\":2,\"url\":\"https://b.com/\",\"indexed\":\"no\"}]}");
        assertTrue(d.ok());
        assertEquals("done", d.status());
        assertEquals(Boolean.TRUE, d.indexed().get("https://a.com/x"));
        assertEquals(Boolean.FALSE, d.indexed().get("https://b.com/"));
        assertFalse(SinbyteClient.parseDetail(400, "{\"message\":\"Invalid apikey\"}").ok());
    }
}
