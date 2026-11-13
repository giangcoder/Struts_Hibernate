package vn.hosoweb.util;

import org.hibernate.Session;
import org.hibernate.SessionFactory;
import org.hibernate.Transaction;
import org.hibernate.cfg.Configuration;
import vn.hosoweb.model.*;

import java.util.function.Consumer;
import java.util.function.Function;

public final class HibernateUtil {
    private static volatile SessionFactory factory;

    private HibernateUtil() { }

    /** URL JDBC: biến môi trường HOSOWEB_DB_URL, mặc định H2 dạng file trong ./data. */
    public static String jdbcUrl() {
        String env = System.getenv("HOSOWEB_DB_URL");
        if (env != null && !env.isBlank()) return env;
        String prop = System.getProperty("hosoweb.db.url");
        if (prop != null && !prop.isBlank()) return prop;
        return "jdbc:h2:file:./data/hosoweb;AUTO_SERVER=TRUE";
    }

    public static SessionFactory factory() {
        if (factory == null) {
            synchronized (HibernateUtil.class) {
                if (factory == null) {
                    Configuration cfg = new Configuration()
                            .setProperty("hibernate.connection.url", jdbcUrl())
                            .setProperty("hibernate.connection.username", env("HOSOWEB_DB_USER", "sa"))
                            .setProperty("hibernate.connection.password", env("HOSOWEB_DB_PASSWORD", ""))
                            .setProperty("hibernate.hbm2ddl.auto", "update")
                            .setProperty("hibernate.connection.pool_size", "10")
                            .setProperty("hibernate.show_sql", "false")
                            .addAnnotatedClass(User.class)
                            .addAnnotatedClass(Project.class)
                            .addAnnotatedClass(Platform.class)
                            .addAnnotatedClass(SocialLink.class);
                    factory = cfg.buildSessionFactory();
                }
            }
        }
        return factory;
    }

    public static <T> T tx(Function<Session, T> work) {
        try (Session s = factory().openSession()) {
            Transaction t = s.beginTransaction();
            try {
                T r = work.apply(s);
                t.commit();
                return r;
            } catch (RuntimeException e) {
                if (t.isActive()) t.rollback();
                throw e;
            }
        }
    }

    public static void run(Consumer<Session> work) {
        tx(s -> { work.accept(s); return null; });
    }

    public static void shutdown() {
        if (factory != null) factory.close();
    }

    private static String env(String k, String d) {
        String v = System.getenv(k);
        return v == null ? d : v;
    }
}
