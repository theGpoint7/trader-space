export default function ApplicationLogo(props) {
    return (
        <svg
            {...props}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 100 100"
            fill="none"
        >
            <rect width="100" height="100" rx="15" fill="black" />
            <text
                x="50%"
                y="50%"
                textAnchor="middle"
                fontFamily="Arial, Helvetica, sans-serif"
                fontWeight="bold"
                fontSize="45"
                fill="white"
                dy=".35em"
            >
                TS
            </text>
        </svg>
    );
}
